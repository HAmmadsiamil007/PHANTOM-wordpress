(function () {
    'use strict'

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    // ─── Spark system ───────────────────────────────────────────
    function initFireSparks() {
        if (reducedMotion) return

        var canvas = document.createElement('canvas')
        canvas.id = 'fireSparksCanvas'
        canvas.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:2;'
        document.body.appendChild(canvas)

        var ctx = canvas.getContext('2d')
        var sparks = []
        var w, h

        function resize() {
            w = canvas.width = window.innerWidth
            h = canvas.height = window.innerHeight
        }
        resize()
        window.addEventListener('resize', resize)

        // ─── Spark types ────────────────────────────────────────
        var TYPES = [
            { label: 'ember', hueMin: 25, hueMax: 45, satMin: 90, satMax: 100, lightMin: 55, lightMax: 75, sizeMin: 1.5, sizeMax: 4, count: 100, vyMin: -0.3, vyMax: -1.0 },
            { label: 'glow', hueMin: 40, hueMax: 55, satMin: 60, satMax: 85, lightMin: 70, lightMax: 90, sizeMin: 3, sizeMax: 7, count: 40, vyMin: -0.15, vyMax: -0.5 },
            { label: 'cinder', hueMin: 15, hueMax: 30, satMin: 30, satMax: 60, lightMin: 30, lightMax: 50, sizeMin: 0.8, sizeMax: 2, count: 60, vyMin: -0.5, vyMax: -1.5 },
        ]

        function Spark(typeDef) {
            this.type = typeDef
            this.reset(true)
        }

        Spark.prototype.reset = function (initial) {
            this.x = Math.random() * w
            this.y = initial ? Math.random() * h : h + 10 + Math.random() * 30
            this.vx = (Math.random() - 0.5) * 0.3
            this.vy = -(this.type.vyMin + Math.random() * (this.type.vyMax - this.type.vyMin))
            this.size = this.type.sizeMin + Math.random() * (this.type.sizeMax - this.type.sizeMin)
            this.life = 0
            this.maxLife = 180 + Math.random() * 280
            this.alpha = 0
            this.hue = this.type.hueMin + Math.random() * (this.type.hueMax - this.type.hueMin)
            this.sat = this.type.satMin + Math.random() * (this.type.satMax - this.type.satMin)
            this.light = this.type.lightMin + Math.random() * (this.type.lightMax - this.type.lightMin)
            this.drift = (Math.random() - 0.5) * 0.02
            this.flickerPhase = Math.random() * Math.PI * 2
            this.flickerSpeed = 0.03 + Math.random() * 0.06
        }

        Spark.prototype.update = function () {
            this.x += this.vx + Math.sin(this.life * this.flickerSpeed + this.flickerPhase) * 0.15
            this.y += this.vy
            this.vy += (Math.random() - 0.5) * 0.015
            this.vx += this.drift
            this.life++

            var progress = this.life / this.maxLife
            if (progress < 0.08) {
                this.alpha = progress / 0.08
            } else if (progress > 0.75) {
                this.alpha = 1 - (progress - 0.75) / 0.25
            } else {
                this.alpha = 0.7 + Math.sin(this.life * this.flickerSpeed + this.flickerPhase) * 0.3
            }

            if (this.life >= this.maxLife || this.y < -20) {
                this.reset(false)
            }
        }

        Spark.prototype.draw = function () {
            ctx.save()
            ctx.globalAlpha = Math.max(0, this.alpha) * 0.85

            var blur = this.type.label === 'glow' ? 25 : this.type.label === 'ember' ? 15 : 6
            ctx.shadowBlur = blur
            ctx.shadowColor = 'hsla(' + this.hue + ',' + this.sat + '%,' + this.light + '%, 0.8)'

            var grad = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, this.size * 2)
            grad.addColorStop(0, 'hsla(' + this.hue + ',' + this.sat + '%,' + Math.min(100, this.light + 20) + '%, 1)')
            grad.addColorStop(0.4, 'hsla(' + this.hue + ',' + this.sat + '%,' + this.light + '%, ' + this.alpha + ')')
            grad.addColorStop(1, 'hsla(' + this.hue + ',' + this.sat + '%,' + this.light + '%, 0)')
            ctx.fillStyle = grad

            ctx.beginPath()
            ctx.arc(this.x, this.y, this.size * 1.5, 0, Math.PI * 2)
            ctx.fill()
            ctx.restore()
        }

        var allSparkDefs = []
        TYPES.forEach(function (t) {
            for (var i = 0; i < t.count; i++) {
                allSparkDefs.push(t)
            }
        })

        for (var i = 0; i < allSparkDefs.length; i++) {
            sparks.push(new Spark(allSparkDefs[i]))
        }

        var running = true

        function animate() {
            if (!running) return
            ctx.clearRect(0, 0, w, h)
            for (var i = 0; i < sparks.length; i++) {
                sparks[i].update()
                sparks[i].draw()
            }
            requestAnimationFrame(animate)
        }
        animate()

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                running = false
            } else {
                running = true
                animate()
            }
        })
    }

    // ─── Gradient overlay (warm bottom glow) ──────────────────
    function initWarmGlow() {
        if (reducedMotion) return
        var div = document.createElement('div')
        div.id = 'warmGlowOverlay'
        div.style.cssText =
            'position:fixed;bottom:0;left:0;width:100%;height:50%;pointer-events:none;z-index:1;' +
            'background:linear-gradient(0deg, rgba(200,149,108,0.12) 0%, rgba(200,149,108,0.04) 40%, transparent 70%);' +
            'will-change:opacity;'
        document.body.appendChild(div)
    }

    function init() {
        initFireSparks()
        initWarmGlow()
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init)
    } else {
        init()
    }
})()
