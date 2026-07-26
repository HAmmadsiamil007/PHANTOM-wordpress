(function () {
    'use strict'

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (reducedMotion || !window.Lenis) return

    var lenis = new Lenis({
        duration: 1.2,
        easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)) },
        orientation: 'vertical',
        gestureOrientation: 'vertical',
        smoothWheel: true,
        wheelMultiplier: 1,
        touchMultiplier: 1.5,
    })

    document.documentElement.style.setProperty('scroll-behavior', 'auto', 'important')

    function raf(time) {
        lenis.raf(time)
        if (typeof ScrollTrigger !== 'undefined') ScrollTrigger.update()
        requestAnimationFrame(raf)
    }
    requestAnimationFrame(raf)

    window.lenis = lenis
})()
