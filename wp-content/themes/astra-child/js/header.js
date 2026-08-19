
/* ================================================
   LUMY RESTAURANT — Custom Header JS
   File: /wp-content/themes/your-child-theme/lumy-header/lumy-header.js
================================================ */
(function () {
  'use strict';

  /* ── Desktop overlay ── */
  var hbgD    = document.getElementById('lumy-hbg-desktop');
  var overlay = document.getElementById('lumy-overlay');
  var ovClose = document.getElementById('lumy-ov-close');

  function openDesktop() {
    overlay.classList.add('on');
    overlay.setAttribute('aria-hidden', 'false');
    hbgD.classList.add('open');
    hbgD.setAttribute('aria-expanded', 'true');
    document.body.classList.add('lumy-menu-open');
  }
  function closeDesktop() {
    overlay.classList.remove('on');
    overlay.setAttribute('aria-hidden', 'true');
    hbgD.classList.remove('open');
    hbgD.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('lumy-menu-open');
  }

  if (hbgD) {
    hbgD.addEventListener('click', function () {
      overlay.classList.contains('on') ? closeDesktop() : openDesktop();
    });
  }
  if (ovClose) {
    ovClose.addEventListener('click', closeDesktop);
  }

  /* ── Mobile overlay ── */
  var hbgM     = document.getElementById('lumy-hbg-mobile');
  var mobOv    = document.getElementById('lumy-mob-overlay');
  var mobClose = document.getElementById('lumy-mob-close');

  function openMobile() {
    mobOv.classList.add('on');
    mobOv.setAttribute('aria-hidden', 'false');
    hbgM.classList.add('open');
    hbgM.setAttribute('aria-expanded', 'true');
    document.body.classList.add('lumy-menu-open');
  }
  function closeMobile() {
    mobOv.classList.remove('on');
    mobOv.setAttribute('aria-hidden', 'true');
    hbgM.classList.remove('open');
    hbgM.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('lumy-menu-open');
  }

  if (hbgM) {
    hbgM.addEventListener('click', function () {
      mobOv.classList.contains('on') ? closeMobile() : openMobile();
    });
  }
  if (mobClose) {
    mobClose.addEventListener('click', closeMobile);
  }

  /* ── ESC closes both ── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeDesktop(); closeMobile(); }
  });

  /* ── Scroll: add .scrolled to desktop header ── */
  var hd = document.getElementById('lumy-hd');
  if (hd) {
    window.addEventListener('scroll', function () {
      hd.classList.toggle('scrolled', window.scrollY > 30);
    }, { passive: true });
  }
})();


document.addEventListener('DOMContentLoaded', function () {

    initHoaXuanThree();

});

async function initHoaXuanThree() {

    const hero = document.querySelector('.mv');
    const canvas = document.getElementById('hoaXuanThree');

    console.log('HX THREE: start');

    if (!hero) {
        console.error('HX THREE: Không tìm thấy .mv');
        return;
    }

    if (!canvas) {
        console.error('HX THREE: Không tìm thấy #hoaXuanThree');
        return;
    }

    let THREE;

    try {

        THREE = await import(
            'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js'
        );

        console.log(
            'HX THREE: Three.js loaded',
            THREE.REVISION
        );

    } catch (error) {

        console.error(
            'HX THREE: Không load được Three.js',
            error
        );

        return;
    }


    /* ==============================
       SCENE
    ============================== */

    const scene = new THREE.Scene();

    const camera = new THREE.PerspectiveCamera(
        55,
        hero.clientWidth / hero.clientHeight,
        0.1,
        100
    );

    camera.position.z = 8;


    /* ==============================
       RENDERER
    ============================== */

    let renderer;

    try {

        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance'
        });

    } catch (error) {

        console.error(
            'HX THREE: WebGLRenderer lỗi',
            error
        );

        return;
    }

    renderer.setClearColor(
        0x000000,
        0
    );

    renderer.setPixelRatio(
        Math.min(
            window.devicePixelRatio || 1,
            1.5
        )
    );


    /* ==============================
       RESIZE
    ============================== */

    function resize() {

        const width = hero.clientWidth;
        const height = hero.clientHeight;

        if (!width || !height) return;

        renderer.setSize(
            width,
            height,
            false
        );

        camera.aspect =
            width / height;

        camera.updateProjectionMatrix();

    }

    resize();


    /* ==============================
       GLOW TEXTURE
    ============================== */

    function makeGlowTexture() {

        const c =
            document.createElement('canvas');

        c.width = 128;
        c.height = 128;

        const ctx =
            c.getContext('2d');

        const gradient =
            ctx.createRadialGradient(
                64,
                64,
                0,
                64,
                64,
                64
            );

        gradient.addColorStop(
            0,
            'rgba(255,255,255,1)'
        );

        gradient.addColorStop(
            0.08,
            'rgba(255,245,180,1)'
        );

        gradient.addColorStop(
            0.2,
            'rgba(255,215,0,1)'
        );

        gradient.addColorStop(
            0.45,
            'rgba(255,180,40,.55)'
        );

        gradient.addColorStop(
            1,
            'rgba(255,170,20,0)'
        );

        ctx.fillStyle =
            gradient;

        ctx.fillRect(
            0,
            0,
            128,
            128
        );

        const texture =
            new THREE.CanvasTexture(c);

        return texture;
    }

    const glowTexture =
        makeGlowTexture();


    /* ==============================
       PARTICLES
    ============================== */

    const mobile =
        window.innerWidth <= 768;

    const count =
        mobile ? 160 : 450;

    const positions =
        new Float32Array(
            count * 3
        );

    const speeds =
        new Float32Array(
            count
        );

    for (let i = 0; i < count; i++) {

        const i3 = i * 3;

        positions[i3] =
            (Math.random() - 0.5)
            * 18;

        positions[i3 + 1] =
            (Math.random() - 0.5)
            * 11;

        positions[i3 + 2] =
            (Math.random() - 0.5)
            * 5;

        speeds[i] =
            0.25 +
            Math.random()
            * 0.45;
    }


    const geometry =
        new THREE.BufferGeometry();

    geometry.setAttribute(
        'position',
        new THREE.BufferAttribute(
            positions,
            3
        )
    );


    const material =
        new THREE.PointsMaterial({

            map: glowTexture,

            color: 0xffd700,

            transparent: true,

            opacity: 0.85,

            depthWrite: false,

            blending:
                THREE.AdditiveBlending,

            /*
             * TEST RÕ TRƯỚC
             */
            size: mobile ? 3 : 5,

            /*
             * Để hạt dễ nhìn trong giai đoạn test.
             */
            sizeAttenuation: false

        });


    const particles =
        new THREE.Points(
            geometry,
            material
        );

    scene.add(particles);

    console.log(
        'HX THREE: particles created',
        count
    );


    /* ==============================
       LARGE BOKEH
    ============================== */

    const bokehCount =
        mobile ? 12 : 28;

    const bokehPositions =
        new Float32Array(
            bokehCount * 3
        );

    for (
        let i = 0;
        i < bokehCount;
        i++
    ) {

        const i3 = i * 3;

        bokehPositions[i3] =
            (Math.random() - 0.5)
            * 17;

        bokehPositions[i3 + 1] =
            (Math.random() - 0.5)
            * 10;

        bokehPositions[i3 + 2] =
            (Math.random() - 0.5)
            * 2;
    }


    const bokehGeometry =
        new THREE.BufferGeometry();

    bokehGeometry.setAttribute(
        'position',
        new THREE.BufferAttribute(
            bokehPositions,
            3
        )
    );


    const bokehMaterial =
        new THREE.PointsMaterial({

            map: glowTexture,

            color: 0xffc640,

            transparent: true,

            opacity: 0.28,

            depthWrite: false,

            blending:
                THREE.AdditiveBlending,

            size: mobile ? 18 : 28,

            sizeAttenuation: false

        });


    const bokeh =
        new THREE.Points(
            bokehGeometry,
            bokehMaterial
        );

    scene.add(bokeh);


    /* ==============================
       MOUSE PARALLAX
    ============================== */

    let targetX = 0;
    let targetY = 0;

    let currentX = 0;
    let currentY = 0;


    hero.addEventListener(
        'pointermove',
        function (event) {

            if (mobile) return;

            const rect =
                hero.getBoundingClientRect();

            targetX =
                (
                    (
                        event.clientX -
                        rect.left
                    )
                    /
                    rect.width
                )
                -
                0.5;

            targetY =
                (
                    (
                        event.clientY -
                        rect.top
                    )
                    /
                    rect.height
                )
                -
                0.5;
        }
    );


    hero.addEventListener(
        'pointerleave',
        function () {

            targetX = 0;
            targetY = 0;

        }
    );


    /* ==============================
       SLIDE BURST
    ============================== */

    let burst = 0;

    const slides =
        hero.querySelectorAll(
            '.mv_slide'
        );

    const observer =
        new MutationObserver(
            function (mutations) {

                mutations.forEach(
                    function (mutation) {

                        if (
                            mutation.target
                                .classList
                                .contains(
                                    'is-active'
                                )
                        ) {

                            burst = 1;

                        }

                    }
                );

            }
        );


    slides.forEach(
        function (slide) {

            observer.observe(
                slide,
                {
                    attributes: true,
                    attributeFilter: [
                        'class'
                    ]
                }
            );

        }
    );


    /* ==============================
       ANIMATION
    ============================== */

    const clock =
        new THREE.Clock();


    function animate() {

        const delta =
            Math.min(
                clock.getDelta(),
                0.05
            );

        const time =
            performance.now()
            * 0.001;


        currentX +=
            (
                targetX -
                currentX
            )
            * 0.035;

        currentY +=
            (
                targetY -
                currentY
            )
            * 0.035;


        /*
         * Camera parallax
         */

        camera.position.x =
            currentX * 0.7;

        camera.position.y =
            -currentY * 0.45;

        camera.lookAt(
            0,
            0,
            0
        );


        /*
         * Particle movement
         */

        const array =
            geometry
                .attributes
                .position
                .array;


        for (
            let i = 0;
            i < count;
            i++
        ) {

            const i3 =
                i * 3;

            array[i3 + 1] +=
                delta *
                speeds[i] *
                (
                    1 +
                    burst * 3
                );


            array[i3] +=
                Math.sin(
                    time * 0.6 +
                    i
                )
                * 0.0007;


            if (
                array[i3 + 1]
                >
                5.5
            ) {

                array[i3 + 1] =
                    -5.5;

                array[i3] =
                    (
                        Math.random() -
                        0.5
                    )
                    * 18;
            }

        }


        geometry
            .attributes
            .position
            .needsUpdate = true;


        /*
         * Three-dimensional movement
         */

        particles.rotation.y =
            currentX * 0.16;

        particles.rotation.x =
            currentY * 0.08;


        bokeh.rotation.y =
            -currentX * 0.1;

        bokeh.rotation.x =
            -currentY * 0.06;


        particles.rotation.z +=
            delta * 0.005;


        /*
         * Slide change energy
         */

        burst *= 0.93;


        renderer.render(
            scene,
            camera
        );

    }


    renderer.setAnimationLoop(
        animate
    );

    /*
     * Three.js recommends setAnimationLoop
     * for the render loop.
     */
    window.addEventListener(
        'resize',
        resize
    );


    console.log(
        '✅ HX THREE ACTIVE'
    );
}