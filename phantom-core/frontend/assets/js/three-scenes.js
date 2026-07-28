(function () {
    'use strict';

    var scenes = [];
    var config = window.PhantomThreeData || {};
    var isRunning = false;

    function hasWebGL() {
        try {
            var canvas = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    function fogParticlesScene(container) {
        var scene = new THREE.Scene();
        scene.background = null;

        var camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 5;

        var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        var cfg = config['fog-particles'] || {};
        var count = cfg.particleCount || 1500;
        var color = new THREE.Color(cfg.color || '#c1121f');
        var particleSize = cfg.size || 0.02;
        var opacity = cfg.opacity || 0.6;
        var rotSpeed = cfg.rotationSpeed || 0.0003;
        var mouseInf = cfg.mouseInfluence || 0.0005;

        var geometry = new THREE.BufferGeometry();
        var positions = new Float32Array(count * 3);
        var sizes = new Float32Array(count);

        for (var i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 20;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 20;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 10 - 2;
            sizes[i] = Math.random() * 2 + 0.5;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        var material = new THREE.PointsMaterial({
            color: color,
            size: particleSize,
            transparent: true,
            opacity: opacity,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
            sizeAttenuation: true,
        });

        var particles = new THREE.Points(geometry, material);
        scene.add(particles);

        var mouseX = 0;
        var mouseY = 0;
        var targetRotX = 0;
        var targetRotY = 0;

        function onMouseMove(event) {
            mouseX = (event.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
        }

        document.addEventListener('mousemove', onMouseMove);

        function animate() {
            targetRotX += (mouseY * Math.PI - targetRotX) * mouseInf;
            targetRotY += (mouseX * Math.PI - targetRotY) * mouseInf;

            particles.rotation.x += rotSpeed + targetRotX;
            particles.rotation.y += rotSpeed * 0.5 + targetRotY;

            renderer.render(scene, camera);
        }

        function resize() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        }

        window.addEventListener('resize', resize);

        return {
            renderer: renderer,
            scene: scene,
            camera: camera,
            animate: animate,
            resize: resize,
            dispose: function () {
                document.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('resize', resize);
                renderer.dispose();
                geometry.dispose();
                material.dispose();
                if (container.contains(renderer.domElement)) {
                    container.removeChild(renderer.domElement);
                }
            },
        };
    }

    function floatingGeoScene(container) {
        var scene = new THREE.Scene();
        scene.background = null;

        var camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 8;

        var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        var cfg = config['floating-geo'] || {};
        var count = cfg.count || 12;
        var sizeRange = cfg.sizeRange || [0.3, 1.2];
        var rotSpeed = cfg.rotationSpeed || 0.005;
        var floatSpeed = cfg.floatSpeed || 0.002;
        var floatAmp = cfg.floatAmplitude || 0.5;
        var colors = cfg.colors || ['#c1121f', '#ff6b35', '#4ecdc4', '#ffffff'];

        var meshes = [];

        function randomColor() {
            return new THREE.Color(colors[Math.floor(Math.random() * colors.length)]);
        }

        for (var i = 0; i < count; i++) {
            var size = sizeRange[0] + Math.random() * (sizeRange[1] - sizeRange[0]);
            var geometry;
            var r = Math.random();

            if (r < 0.4) {
                geometry = new THREE.IcosahedronGeometry(size, 0);
            } else if (r < 0.7) {
                geometry = new THREE.TorusKnotGeometry(size * 0.6, size * 0.25, 64, 8);
            } else {
                geometry = new THREE.OctahedronGeometry(size, 0);
            }

            var material = new THREE.MeshStandardMaterial({
                color: randomColor(),
                metalness: 0.3 + Math.random() * 0.4,
                roughness: 0.2 + Math.random() * 0.4,
                transparent: true,
                opacity: 0.6 + Math.random() * 0.4,
                wireframe: Math.random() > 0.7,
            });

            var mesh = new THREE.Mesh(geometry, material);
            mesh.position.x = (Math.random() - 0.5) * 12;
            mesh.position.y = (Math.random() - 0.5) * 8;
            mesh.position.z = (Math.random() - 0.5) * 4 - 2;

            var floatOffset = Math.random() * Math.PI * 2;
            var rotSpeedX = (Math.random() - 0.5) * rotSpeed * 2;
            var rotSpeedY = (Math.random() - 0.5) * rotSpeed * 2;
            var rotSpeedZ = (Math.random() - 0.5) * rotSpeed;

            scene.add(mesh);

            meshes.push({
                mesh: mesh,
                floatOffset: floatOffset,
                origY: mesh.position.y,
                rotSpeedX: rotSpeedX,
                rotSpeedY: rotSpeedY,
                rotSpeedZ: rotSpeedZ,
            });
        }

        var ambientLight = new THREE.AmbientLight(0x404040);
        scene.add(ambientLight);

        var dirLight = new THREE.DirectionalLight(0xffffff, 1);
        dirLight.position.set(5, 10, 7);
        scene.add(dirLight);

        var dirLight2 = new THREE.DirectionalLight(0x8888ff, 0.5);
        dirLight2.position.set(-5, -5, -5);
        scene.add(dirLight2);

        var time = 0;

        function animate() {
            time += floatSpeed;
            for (var i = 0; i < meshes.length; i++) {
                var m = meshes[i];
                m.mesh.position.y = m.origY + Math.sin(time + m.floatOffset) * floatAmp;
                m.mesh.rotation.x += m.rotSpeedX;
                m.mesh.rotation.y += m.rotSpeedY;
                m.mesh.rotation.z += m.rotSpeedZ;
            }

            renderer.render(scene, camera);
        }

        function resize() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        }

        window.addEventListener('resize', resize);

        return {
            renderer: renderer,
            scene: scene,
            camera: camera,
            animate: animate,
            resize: resize,
            dispose: function () {
                window.removeEventListener('resize', resize);
                for (var i = 0; i < meshes.length; i++) {
                    scene.remove(meshes[i].mesh);
                    meshes[i].mesh.geometry.dispose();
                    meshes[i].mesh.material.dispose();
                }
                renderer.dispose();
                if (container.contains(renderer.domElement)) {
                    container.removeChild(renderer.domElement);
                }
            },
        };
    }

    function starFieldScene(container) {
        var scene = new THREE.Scene();
        scene.background = null;

        var camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 30;

        var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        var cfg = config['star-field'] || {};
        var starCount = cfg.starCount || 3000;
        var particleSize = cfg.size || 0.015;
        var spread = cfg.spread || 50;
        var colorCycleSpeed = cfg.colorCycleSpeed || 0.001;
        var twinkleSpeed = cfg.twinkleSpeed || 0.005;
        var twinkleIntensity = cfg.twinkleIntensity || 0.3;

        var geometry = new THREE.BufferGeometry();
        var positions = new Float32Array(starCount * 3);
        var colors = new Float32Array(starCount * 3);
        var sizes = new Float32Array(starCount);

        var color = new THREE.Color();

        for (var i = 0; i < starCount; i++) {
            positions[i * 3] = (Math.random() - 0.5) * spread;
            positions[i * 3 + 1] = (Math.random() - 0.5) * spread;
            positions[i * 3 + 2] = (Math.random() - 0.5) * spread;

            color.setHSL(Math.random(), 0.3 + Math.random() * 0.5, 0.5 + Math.random() * 0.5);
            colors[i * 3] = color.r;
            colors[i * 3 + 1] = color.g;
            colors[i * 3 + 2] = color.b;

            sizes[i] = 0.5 + Math.random() * 2;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        var material = new THREE.PointsMaterial({
            size: particleSize,
            vertexColors: true,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
            sizeAttenuation: true,
        });

        var particles = new THREE.Points(geometry, material);
        scene.add(particles);

        var time = 0;
        var colorHue = 0;

        function animate() {
            time += twinkleSpeed;
            colorHue += colorCycleSpeed;

            var positionsAttr = geometry.attributes.position;
            var colorAttr = geometry.attributes.color;
            var posArray = positionsAttr.array;
            var colorArray = colorAttr.array;

            for (var i = 0; i < starCount; i++) {
                var twinkle = 0.7 + Math.sin(time * sizes[i] + i) * twinkleIntensity;
                var hue = (colorHue + i * 0.001) % 1;
                color.setHSL(hue, 0.6, 0.4 + twinkle * 0.4);
                colorArray[i * 3] = color.r;
                colorArray[i * 3 + 1] = color.g;
                colorArray[i * 3 + 2] = color.b;
            }

            colorAttr.needsUpdate = true;

            particles.rotation.y += 0.0002;

            renderer.render(scene, camera);
        }

        function resize() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        }

        window.addEventListener('resize', resize);

        return {
            renderer: renderer,
            scene: scene,
            camera: camera,
            animate: animate,
            resize: resize,
            dispose: function () {
                window.removeEventListener('resize', resize);
                renderer.dispose();
                geometry.dispose();
                material.dispose();
                if (container.contains(renderer.domElement)) {
                    container.removeChild(renderer.domElement);
                }
            },
        };
    }

    var sceneFactories = {
        'fog-particles': fogParticlesScene,
        'floating-geo': floatingGeoScene,
        'star-field': starFieldScene,
    };

    function initScenes() {
        if (isRunning) return;
        isRunning = true;

        if (!hasWebGL()) {
            if (typeof console !== 'undefined') {
                console.warn('[Three Scenes] WebGL is not supported on this device. 3D scenes disabled.');
            }
            return;
        }

        var containers = document.querySelectorAll('[data-three-scene]');
        if (containers.length === 0) return;

        for (var i = 0; i < containers.length; i++) {
            var container = containers[i];
            var sceneName = container.getAttribute('data-three-scene');
            var factory = sceneFactories[sceneName];
            if (!factory) continue;

            try {
                var instance = factory(container);
                scenes.push(instance);
            } catch (e) {
                if (typeof console !== 'undefined') {
                    console.warn('[Three Scenes] Failed to init scene "' + sceneName + '":', e);
                }
            }
        }
    }

    function startLoop() {
        if (scenes.length === 0) return;

        var animFrameId = null;

        function loop() {
            animFrameId = requestAnimationFrame(loop);
            for (var i = 0; i < scenes.length; i++) {
                if (scenes[i].animate) scenes[i].animate();
            }
        }

        loop();
    }

    function disposeAll() {
        for (var i = 0; i < scenes.length; i++) {
            if (scenes[i].dispose) scenes[i].dispose();
        }
        scenes = [];
        isRunning = false;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initScenes();
            startLoop();
        });
    } else {
        initScenes();
        startLoop();
    }

    document.addEventListener('pagehide', disposeAll);
    window.addEventListener('beforeunload', disposeAll);

    document.addEventListener('phantom-navigate', function () {
        disposeAll();
        setTimeout(function () {
            initScenes();
            startLoop();
        }, 50);
    });

})();
