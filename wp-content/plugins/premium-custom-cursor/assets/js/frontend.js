(function () {
	'use strict';

	var cfg = window.PCCursorSettings || {};
	if (!cfg.enabled) return;

	var root = document.documentElement;
	var finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
	if (!finePointer) return;

	function viewportAllowed() {
		var min = parseInt(cfg.disableBelow || 0, 10);
		return !min || window.innerWidth >= min;
	}
	if (!viewportAllowed()) return;

	var preset = String(cfg.preset || 'glass');
	var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	var dot = document.createElement('div');
	dot.className = 'pcc-dot';

	var ring = document.createElement('div');
	ring.className = 'pcc-ring pcc-preset-' + preset + ' ' + (cfg.ambientFx ? 'pcc-ambient-on' : 'pcc-ambient-off');

	var visual = document.createElement('div');
	visual.className = 'pcc-ring__visual';

	var shine = document.createElement('i');
	shine.className = 'pcc-shine';

	var orbitA = document.createElement('i');
	orbitA.className = 'pcc-orbit pcc-orbit-a';

	var orbitB = document.createElement('i');
	orbitB.className = 'pcc-orbit pcc-orbit-b';

	var label = document.createElement('span');
	label.className = 'pcc-ring__label';

	visual.appendChild(shine);
	visual.appendChild(orbitA);
	visual.appendChild(orbitB);

	for (var si = 0; si < 4; si++) {
		var spark = document.createElement('i');
		spark.className = 'pcc-spark';
		visual.appendChild(spark);
	}

	visual.appendChild(label);
	ring.appendChild(visual);

	document.body.appendChild(dot);
	document.body.appendChild(ring);

	if (!cfg.dotEnabled) dot.style.display = 'none';
	if (cfg.hideNative) root.classList.add('pcc-hide-native');

	var mouseX = window.innerWidth / 2;
	var mouseY = window.innerHeight / 2;
	var prevMouseX = mouseX;
	var prevMouseY = mouseY;
	var ringX = mouseX;
	var ringY = mouseY;
	var dotX = mouseX;
	var dotY = mouseY;
	var velocityX = 0;
	var velocityY = 0;

	var ringSpeed = Number(cfg.followSpeed || 0.12);
	var dotSpeed = Number(cfg.dotSpeed || 0.65);
	if (prefersReduced) {
		ringSpeed = Math.max(ringSpeed, 0.32);
		dotSpeed = 1;
	}
	ringSpeed = Math.min(1, Math.max(0.02, ringSpeed));
	dotSpeed = Math.min(1, Math.max(0.05, dotSpeed));

	function safeClosest(target, selector) {
		if (!target || !selector || !target.closest) return null;
		try { return target.closest(selector); } catch (e) { return null; }
	}

	function updateHover(target) {
		var labelTarget = cfg.labelEnabled ? safeClosest(target, cfg.labelSelector) : null;
		var interactiveTarget = cfg.hoverEnabled ? safeClosest(target, cfg.interactiveSelector) : null;

		if (labelTarget) {
			label.textContent = labelTarget.getAttribute('data-pcc-label') || cfg.defaultLabel || 'VIEW';
			ring.classList.add('pcc-has-label');
			ring.classList.remove('pcc-is-hover');
			dot.classList.add('pcc-dot-hidden');
			return;
		}

		label.textContent = '';
		ring.classList.remove('pcc-has-label');

		if (interactiveTarget) {
			ring.classList.add('pcc-is-hover');
			dot.classList.add('pcc-dot-hidden');
		} else {
			ring.classList.remove('pcc-is-hover');
			dot.classList.remove('pcc-dot-hidden');
		}
	}

	function onMove(e) {
		if (!viewportAllowed()) {
			root.classList.remove('pcc-active', 'pcc-hide-native');
			return;
		}

		prevMouseX = mouseX;
		prevMouseY = mouseY;
		mouseX = e.clientX;
		mouseY = e.clientY;

		velocityX = mouseX - prevMouseX;
		velocityY = mouseY - prevMouseY;

		root.classList.add('pcc-active');
		root.classList.remove('pcc-hidden');
		if (cfg.hideNative) root.classList.add('pcc-hide-native');

		updateHover(e.target);
	}

	/* Trail */
	var trails = [];
	var autoTrailPresets = { comet: true, aurora: true };
	var trailWanted = !prefersReduced && (cfg.trailEnabled || autoTrailPresets[preset]);
	var trailCount = Math.max(2, Math.min(20, parseInt(cfg.trailLength || 8, 10)));

	if (trailWanted) {
		for (var ti = 0; ti < trailCount; ti++) {
			var trail = document.createElement('i');
			trail.className = 'pcc-trail' + (preset === 'comet' ? ' pcc-trail-comet' : '');
			trail.style.setProperty('--pcc-trail-alpha', String(1 - (ti / trailCount) * 0.82));
			var scale = Math.max(0.18, 1 - ti / trailCount);
			trail.style.width = (9 * scale) + 'px';
			trail.style.height = (9 * scale) + 'px';
			trail.style.marginLeft = (-4.5 * scale) + 'px';
			trail.style.marginTop = (-4.5 * scale) + 'px';
			document.body.appendChild(trail);
			trails.push({ el: trail, x: mouseX, y: mouseY });
		}
	}

	function animate() {
		dotX += (mouseX - dotX) * dotSpeed;
		dotY += (mouseY - dotY) * dotSpeed;
		ringX += (mouseX - ringX) * ringSpeed;
		ringY += (mouseY - ringY) * ringSpeed;

		dot.style.transform = 'translate3d(' + dotX + 'px,' + dotY + 'px,0) translate(-50%,-50%)';
		ring.style.transform = 'translate3d(' + ringX + 'px,' + ringY + 'px,0) translate(-50%,-50%)';

		if (cfg.tiltEnabled && !prefersReduced) {
			var strength = Math.max(0, Math.min(28, Number(cfg.tiltStrength || 12)));
			var tx = Math.max(-strength, Math.min(strength, -velocityY * 0.34));
			var ty = Math.max(-strength, Math.min(strength, velocityX * 0.34));
			var rz = Math.max(-8, Math.min(8, velocityX * 0.08));
			visual.style.transform = 'rotateX(' + tx + 'deg) rotateY(' + ty + 'deg) rotateZ(' + rz + 'deg)';
			velocityX *= 0.88;
			velocityY *= 0.88;
		}

		if (trails.length) {
			var leadX = mouseX;
			var leadY = mouseY;
			for (var i = 0; i < trails.length; i++) {
				var item = trails[i];
				var speed = Math.max(0.08, 0.34 - i * 0.012);
				item.x += (leadX - item.x) * speed;
				item.y += (leadY - item.y) * speed;
				item.el.style.transform = 'translate3d(' + item.x + 'px,' + item.y + 'px,0)';
				leadX = item.x;
				leadY = item.y;
			}
		}

		window.requestAnimationFrame(animate);
	}

	function makeClickLayer(x, y) {
		var layer = document.createElement('div');
		layer.className = 'pcc-click-fx';
		layer.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
		document.body.appendChild(layer);
		window.setTimeout(function () {
			if (layer && layer.parentNode) layer.parentNode.removeChild(layer);
		}, 1200);
		return layer;
	}

	function addWave(layer, secondary) {
		var wave = document.createElement('i');
		wave.className = 'pcc-click-wave' + (secondary ? ' is-secondary' : '');
		layer.appendChild(wave);
	}

	function addShock(layer) {
		var shock = document.createElement('i');
		shock.className = 'pcc-click-shock';
		layer.appendChild(shock);
	}

	function particleBurst(layer, type, count) {
		count = Math.max(4, Math.min(30, count || 14));
		for (var i = 0; i < count; i++) {
			var p = document.createElement('i');
			var angle = (Math.PI * 2 * i / count) + (Math.random() - 0.5) * 0.28;
			var distance = 36 + Math.random() * 55;
			var size = 3 + Math.random() * 5;
			var color = i % 2 ? (cfg.fxSecondary || '#f4c95d') : (cfg.fxPrimary || '#ffffff');

			p.className = 'pcc-particle';
			if (type === 'diamond') p.className += ' is-diamond';
			if (type === 'spark') p.className += ' is-spark';
			if (type === 'fire') p.className += ' is-fire';

			p.style.setProperty('--pcc-dx', (Math.cos(angle) * distance).toFixed(1) + 'px');
			p.style.setProperty('--pcc-dy', (Math.sin(angle) * distance).toFixed(1) + 'px');
			p.style.setProperty('--pcc-rot', ((Math.random() * 300) - 150).toFixed(0) + 'deg');
			p.style.setProperty('--pcc-particle-size', size.toFixed(1) + 'px');
			p.style.setProperty('--pcc-particle-color', color);
			p.style.animationDelay = (Math.random() * 0.055).toFixed(3) + 's';

			layer.appendChild(p);
		}
	}

	function runClickEffect(x, y) {
		var effect = String(cfg.clickEffect || 'none');
		if (effect === 'none' || effect === 'shrink' || effect === 'pulse') return;

		var layer = makeClickLayer(x, y);
		var count = parseInt(cfg.clickParticles || 14, 10);

		if (effect === 'ripple') {
			addWave(layer, false);
		} else if (effect === 'double_ripple') {
			addWave(layer, false);
			addWave(layer, true);
		} else if (effect === 'spark_burst') {
			addWave(layer, false);
			particleBurst(layer, 'spark', count);
		} else if (effect === 'diamond_burst') {
			addWave(layer, true);
			particleBurst(layer, 'diamond', count);
		} else if (effect === 'shockwave') {
			addShock(layer);
			addWave(layer, false);
		} else if (effect === 'firework') {
			addShock(layer);
			particleBurst(layer, 'fire', count);
			particleBurst(layer, 'spark', Math.max(4, Math.floor(count / 2)));
		}
	}

	document.addEventListener('pointermove', onMove, { passive: true });

	document.addEventListener('mouseleave', function () {
		root.classList.add('pcc-hidden');
	});

	document.addEventListener('mouseenter', function () {
		if (viewportAllowed()) root.classList.remove('pcc-hidden');
	});

	document.addEventListener('pointerdown', function (e) {
		if (cfg.clickEffect === 'shrink') {
			ring.classList.add('pcc-click-shrink');
		} else if (cfg.clickEffect === 'pulse') {
			if (visual.animate) {
				visual.animate(
					[
						{ scale: '1' },
						{ scale: '.68' },
						{ scale: '1' }
					],
					{ duration: 350, easing: 'cubic-bezier(.2,.8,.2,1)' }
				);
			}
		}
		runClickEffect(e.clientX, e.clientY);
	});

	document.addEventListener('pointerup', function () {
		ring.classList.remove('pcc-click-shrink');
	});

	window.addEventListener('blur', function () {
		root.classList.add('pcc-hidden');
	});

	window.addEventListener('resize', function () {
		if (!viewportAllowed()) {
			root.classList.remove('pcc-active', 'pcc-hide-native');
			root.classList.add('pcc-hidden');
		}
	});

	animate();
})();
