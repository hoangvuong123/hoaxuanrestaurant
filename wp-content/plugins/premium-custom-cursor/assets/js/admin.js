(function () {
	'use strict';

	var form = document.getElementById('pcc-settings-form');
	var preview = document.getElementById('pcc-preview');
	var dot = document.getElementById('pcc-preview-dot');
	var ring = document.getElementById('pcc-preview-ring');
	var visual = document.getElementById('pcc-preview-visual');
	var labelSpan = ring ? ring.querySelector('.pcc-demo-label') : null;
	var styleName = document.getElementById('pcc-preview-style-name');
	var trailsLayer = document.getElementById('pcc-preview-trails');

	if (!form || !preview || !dot || !ring || !visual) return;

	function get(id) { return document.getElementById('pcc_' + id); }
	function val(id, fallback) { var el = get(id); return el ? el.value : fallback; }

	function hexToRgba(hex, alpha) {
		hex = String(hex || '#ffffff').replace('#', '');
		if (hex.length === 3) hex = hex.split('').map(function (x) { return x + x; }).join('');
		var n = parseInt(hex, 16);
		var r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
		return 'rgba(' + r + ',' + g + ',' + b + ',' + Number(alpha || 0) + ')';
	}

	function updatePreview() {
		var style = val('preset', 'glass');
		preview.dataset.style = style;
		if (styleName) styleName.textContent = style.replace('_', ' ');

		preview.style.setProperty('--demo-dot-size', val('dot_size', 6) + 'px');
		preview.style.setProperty('--demo-dot-color', val('dot_color', '#ffffff'));
		preview.style.setProperty('--demo-dot-opacity', val('dot_opacity', 1));
		preview.style.setProperty('--demo-ring-size', val('ring_size', 42) + 'px');
		preview.style.setProperty('--demo-border-width', val('ring_border_width', 1) + 'px');
		preview.style.setProperty('--demo-border', hexToRgba(val('ring_border_color', '#ffffff'), val('ring_border_opacity', .7)));
		preview.style.setProperty('--demo-bg', hexToRgba(val('ring_bg_color', '#ffffff'), val('ring_bg_opacity', .02)));
		preview.style.setProperty('--demo-text', val('ring_text_color', '#ffffff'));
		preview.style.setProperty('--demo-radius', val('ring_radius', 50) + '%');
		preview.style.setProperty('--demo-blur', val('ring_blur', 0) + 'px');
		preview.style.setProperty('--demo-fx-primary', val('fx_primary', '#ffffff'));
		preview.style.setProperty('--demo-fx-secondary', val('fx_secondary', '#f4c95d'));
		preview.style.setProperty('--demo-glow', val('glow_strength', 1));
		preview.style.setProperty('--demo-hover-size', val('hover_size', 68) + 'px');
		preview.style.setProperty('--demo-hover-bg', hexToRgba(val('hover_bg_color', '#ffffff'), val('hover_bg_opacity', .1)));
		preview.style.setProperty('--demo-hover-border', hexToRgba(val('hover_border_color', '#ffffff'), val('hover_border_opacity', .25)));
		preview.style.setProperty('--demo-label-size', val('label_size', 84) + 'px');
		preview.style.setProperty('--demo-label-bg', val('label_bg_color', '#ffffff'));
		preview.style.setProperty('--demo-label-text', val('label_text_color', '#111111'));
		preview.style.setProperty('--demo-label-border', hexToRgba(val('label_border_color', '#ffffff'), val('label_border_opacity', 0)));
		preview.style.setProperty('--demo-font-size', val('label_font_size', 9) + 'px');
		preview.style.setProperty('--demo-letter-spacing', val('label_letter_spacing', .14) + 'em');
		preview.style.setProperty('--demo-click-size', val('click_size', 120) + 'px');
	}

	var presets = {
		minimal:{dot_size:4,ring_size:30,ring_border_width:0,ring_bg_opacity:0,blend_mode:'difference',follow_speed:.18,tilt_strength:4},
		outline:{dot_size:5,ring_size:44,ring_border_width:1,ring_border_opacity:.75,ring_bg_opacity:0,blend_mode:'difference',follow_speed:.12,tilt_strength:7},
		glass:{dot_size:6,ring_size:44,ring_border_width:1,ring_border_opacity:.45,ring_bg_opacity:.08,ring_blur:6,blend_mode:'normal',follow_speed:.11,tilt_strength:12},
		solid:{dot_size:4,ring_size:38,ring_border_width:0,ring_bg_opacity:.94,blend_mode:'normal',follow_speed:.15,tilt_strength:8},
		neon:{dot_size:5,ring_size:45,ring_border_width:1,ring_border_opacity:1,ring_bg_opacity:.01,blend_mode:'screen',follow_speed:.1,tilt_strength:10,glow_strength:1.4},
		invert:{dot_size:6,ring_size:46,ring_border_width:1,ring_border_opacity:.8,ring_bg_opacity:0,blend_mode:'difference',follow_speed:.12,tilt_strength:6},
		hologram:{dot_size:4,ring_size:50,ring_border_width:2,ring_bg_opacity:.08,blend_mode:'normal',follow_speed:.09,tilt_strength:14,glow_strength:1.3},
		crystal3d:{dot_size:5,ring_size:52,ring_border_width:1,ring_border_opacity:.5,ring_bg_opacity:.07,ring_blur:5,blend_mode:'normal',follow_speed:.08,tilt_strength:18,glow_strength:1.1},
		chrome3d:{dot_size:4,ring_size:50,ring_border_width:1,ring_border_opacity:.75,ring_bg_opacity:.1,blend_mode:'normal',follow_speed:.085,tilt_strength:20,glow_strength:.7},
		aurora:{dot_size:4,ring_size:48,ring_border_width:0,ring_bg_opacity:.2,blend_mode:'screen',follow_speed:.08,tilt_strength:11,glow_strength:1.5},
		bling:{dot_size:4,ring_size:47,ring_border_width:1,ring_border_opacity:.9,ring_bg_opacity:.04,blend_mode:'normal',follow_speed:.095,tilt_strength:14,glow_strength:1.5,fx_secondary:'#f4c95d'},
		orbit:{dot_size:4,ring_size:44,ring_border_width:1,ring_border_opacity:.55,ring_bg_opacity:.05,blend_mode:'normal',follow_speed:.075,tilt_strength:18,glow_strength:1.1},
		cyber:{dot_size:4,ring_size:48,ring_border_width:1,ring_border_opacity:1,ring_radius:18,ring_bg_opacity:.05,blend_mode:'normal',follow_speed:.11,tilt_strength:10,glow_strength:1.2,fx_primary:'#55ffff'},
		liquid:{dot_size:4,ring_size:50,ring_border_width:1,ring_border_opacity:.45,ring_bg_opacity:.18,blend_mode:'normal',follow_speed:.07,tilt_strength:14,glow_strength:.8},
		comet:{dot_size:5,ring_size:38,ring_border_width:1,ring_border_opacity:1,ring_bg_opacity:.01,blend_mode:'screen',follow_speed:.16,tilt_strength:8,glow_strength:1.4},
		luxe_gold:{dot_size:4,ring_size:48,ring_border_width:1,ring_border_opacity:1,ring_bg_opacity:.05,blend_mode:'normal',follow_speed:.09,tilt_strength:15,glow_strength:1.2,fx_primary:'#fff1ad',fx_secondary:'#d2a13b'}
	};

	var preset = get('preset');
	if (preset) {
		preset.addEventListener('change', function () {
			var data = presets[preset.value] || {};
			Object.keys(data).forEach(function (key) {
				var el = get(key);
				if (el) el.value = data[key];
			});
			updatePreview();
		});
	}

	form.addEventListener('input', updatePreview);
	form.addEventListener('change', updatePreview);

	var x = preview.clientWidth / 2, y = preview.clientHeight / 2;
	var px = x, py = y, dx = x, dy = y;
	var vx = 0, vy = 0, lastX = x, lastY = y;
	var demoTrails = [];

	function rebuildTrails() {
		demoTrails.forEach(function (t) { if (t.el.parentNode) t.el.parentNode.removeChild(t.el); });
		demoTrails = [];
		var style = val('preset', 'glass');
		var trailCheckbox = document.querySelector('input[name="pcc_settings[trail_enabled]"]');
		var enabled = (trailCheckbox && trailCheckbox.checked) || style === 'comet' || style === 'aurora';
		if (!enabled) return;
		var count = Math.max(2, Math.min(14, parseInt(val('trail_length', 8), 10)));
		for (var i = 0; i < count; i++) {
			var t = document.createElement('i');
			t.className = 'pcc-demo-trail';
			t.style.opacity = String(Number(val('trail_opacity', .26)) * (1 - i / count * .82));
			trailsLayer.appendChild(t);
			demoTrails.push({el:t,x:x,y:y});
		}
	}

	preview.addEventListener('mousemove', function (e) {
		var r = preview.getBoundingClientRect();
		lastX = x; lastY = y;
		x = e.clientX - r.left;
		y = e.clientY - r.top;
		vx = x - lastX; vy = y - lastY;
	});

	preview.addEventListener('mouseover', function (e) {
		var demo = e.target.closest('[data-pcc-demo-label]');
		if (demo) {
			preview.classList.add('is-label');
			preview.classList.remove('is-hover');
			labelSpan.textContent = val('default_label', 'VIEW');
		} else if (e.target.closest('a,button')) {
			preview.classList.add('is-hover');
		}
	});

	preview.addEventListener('mouseout', function (e) {
		if (!preview.contains(e.relatedTarget) || !e.relatedTarget.closest || !e.relatedTarget.closest('[data-pcc-demo-label]')) {
			preview.classList.remove('is-label');
			labelSpan.textContent = '';
		}
		if (!e.relatedTarget || !e.relatedTarget.closest || !e.relatedTarget.closest('a,button')) {
			preview.classList.remove('is-hover');
		}
	});

	function demoWave(cx, cy, secondary, shock) {
		var w = document.createElement('i');
		w.className = 'pcc-demo-click' + (secondary ? ' secondary' : '') + (shock ? ' shock' : '');
		w.style.left = cx + 'px'; w.style.top = cy + 'px';
		preview.appendChild(w);
		setTimeout(function(){ if(w.parentNode) w.parentNode.removeChild(w); }, 900);
	}

	function demoBurst(cx, cy, type, count) {
		count = Math.max(4, Math.min(24, count));
		for (var i=0;i<count;i++) {
			var p=document.createElement('i');
			var a=Math.PI*2*i/count+(Math.random()-.5)*.25;
			var d=32+Math.random()*48;
			p.className='pcc-demo-particle '+type;
			p.style.left=cx+'px'; p.style.top=cy+'px';
			p.style.setProperty('--dx',(Math.cos(a)*d).toFixed(1)+'px');
			p.style.setProperty('--dy',(Math.sin(a)*d).toFixed(1)+'px');
			p.style.setProperty('--rot',((Math.random()*260)-130).toFixed(0)+'deg');
			p.style.setProperty('--p', i%2 ? val('fx_secondary','#f4c95d') : val('fx_primary','#ffffff'));
			preview.appendChild(p);
			(function(node){ setTimeout(function(){ if(node.parentNode)node.parentNode.removeChild(node); },850); })(p);
		}
	}

	preview.addEventListener('mousedown', function (e) {
		var r=preview.getBoundingClientRect(), cx=e.clientX-r.left, cy=e.clientY-r.top;
		var fx=val('click_effect','ripple'), count=parseInt(val('click_particles',14),10);

		if(fx==='shrink') ring.style.scale='.72';
		else if(fx==='pulse') {
			ring.animate([{scale:'1'},{scale:'.68'},{scale:'1'}],{duration:350,easing:'cubic-bezier(.2,.8,.2,1)'});
		}
		else if(fx==='ripple') demoWave(cx,cy,false,false);
		else if(fx==='double_ripple'){ demoWave(cx,cy,false,false); demoWave(cx,cy,true,false); }
		else if(fx==='spark_burst'){ demoWave(cx,cy,false,false); demoBurst(cx,cy,'spark',count); }
		else if(fx==='diamond_burst'){ demoWave(cx,cy,true,false); demoBurst(cx,cy,'diamond',count); }
		else if(fx==='shockwave'){ demoWave(cx,cy,false,true); demoWave(cx,cy,false,false); }
		else if(fx==='firework'){ demoWave(cx,cy,false,true); demoBurst(cx,cy,'fire',count); demoBurst(cx,cy,'spark',Math.max(4,Math.floor(count/2))); }
	});

	preview.addEventListener('mouseup', function(){ ring.style.scale=''; });

	function animate() {
		var ds=Number(val('dot_speed',.65)), rs=Number(val('follow_speed',.12));
		dx += (x-dx)*ds; dy += (y-dy)*ds;
		px += (x-px)*rs; py += (y-py)*rs;
		dot.style.left=dx+'px'; dot.style.top=dy+'px';
		ring.style.left=px+'px'; ring.style.top=py+'px';

		var tiltBox = document.querySelector('input[name="pcc_settings[tilt_enabled]"]');
		if (tiltBox && tiltBox.checked) {
			var s=Math.max(0,Math.min(28,Number(val('tilt_strength',12))));
			var tx=Math.max(-s,Math.min(s,-vy*.35)), ty=Math.max(-s,Math.min(s,vx*.35));
			visual.style.transform='rotateX('+tx+'deg) rotateY('+ty+'deg)';
			vx*=.88; vy*=.88;
		} else visual.style.transform='';

		if(demoTrails.length){
			var lx=x,ly=y;
			for(var i=0;i<demoTrails.length;i++){
				var t=demoTrails[i], sp=Math.max(.09,.33-i*.015);
				t.x+=(lx-t.x)*sp; t.y+=(ly-t.y)*sp;
				t.el.style.left=t.x+'px'; t.el.style.top=t.y+'px';
				lx=t.x;ly=t.y;
			}
		}
		requestAnimationFrame(animate);
	}

	form.addEventListener('change', function(e){
		if (e.target && (e.target.id==='pcc_preset' || e.target.name==='pcc_settings[trail_enabled]' || e.target.id==='pcc_trail_length' || e.target.id==='pcc_trail_opacity')) rebuildTrails();
	});

	updatePreview();
	rebuildTrails();
	animate();
})();
