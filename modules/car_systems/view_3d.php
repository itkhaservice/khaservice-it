<?php
// Ngăn chặn truy cập trực tiếp
if (!defined('BASE_URL')) {
    define('BASE_URL', $final_base);
}
?>
<div class="action-header">
    <div class="header-left">
        <h2><i class="fas fa-microchip"></i> Kỹ thuật: Luồng dữ liệu Hệ thống Bãi xe</h2>
    </div>
    <div class="header-right">
        <button id="btn-play-flow" class="btn btn-primary"><i class="fas fa-play"></i> Chạy quy trình (Simulation)</button>
        <a href="index.php?page=car_systems/list" class="btn btn-secondary"><i class="fas fa-list"></i> Danh sách</a>
    </div>
</div>

<div class="view-container" style="background: #0a0a0a; border-radius: 12px; overflow: hidden; position: relative; height: 85vh; border: 1px solid #333;">
    <!-- Step Indicator Overlay -->
    <div id="step-display" style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.85); color: #fff; padding: 15px 30px; border-radius: 50px; border: 2px solid #108042; font-family: 'Segoe UI', sans-serif; z-index: 10; display: none; text-align: center; min-width: 400px; box-shadow: 0 0 20px rgba(16,128,66,0.3);">
        <h4 id="step-title" style="margin: 0; color: #108042; text-transform: uppercase; font-size: 14px; letter-spacing: 1px;">BƯỚC 1</h4>
        <p id="step-desc" style="margin: 5px 0 0 0; font-size: 16px;">Xe máy đi tới vị trí dừng trước Barrier</p>
    </div>

    <!-- Connection Legend -->
    <div style="position: absolute; top: 20px; left: 20px; background: rgba(0,0,0,0.7); padding: 15px; border-radius: 8px; color: #ccc; font-size: 12px; border: 1px solid #444; pointer-events: none;">
        <div style="margin-bottom: 8px; font-weight: bold; color: #fff; border-bottom: 1px solid #444; padding-bottom: 5px;">KẾT NỐI HỆ THỐNG</div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;"><span style="width: 20px; height: 3px; background: #3b82f6;"></span> Dữ liệu hình ảnh (TCP/IP)</div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;"><span style="width: 20px; height: 3px; background: #facc15;"></span> Dữ liệu thẻ (Serial/USB)</div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;"><span style="width: 20px; height: 3px; background: #22c55e;"></span> Tín hiệu điều khiển (Relay)</div>
        <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 20px; height: 3px; background: #ef4444;"></span> Tín hiệu vòng từ (Digital)</div>
    </div>

    <div id="canvas-container" style="width: 100%; height: 100%;"></div>
</div>

<script type="importmap">
    {
        "imports": {
            "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
            "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
        }
    }
</script>

<script type="module">
    import * as THREE from 'three';
    import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

    // --- SETUP ---
    const container = document.getElementById('canvas-container');
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0a0a);
    
    const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
    camera.position.set(18, 12, 18);
    
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.shadowMap.enabled = true;
    container.appendChild(renderer.domElement);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;

    // --- LIGHTS ---
    scene.add(new THREE.AmbientLight(0xffffff, 0.4));
    const pointLight = new THREE.PointLight(0xffffff, 100);
    pointLight.position.set(10, 15, 10);
    scene.add(pointLight);

    // --- ENVIRONMENT ---
    const grid = new THREE.GridHelper(40, 40, 0x222222, 0x111111);
    scene.add(grid);

    // --- DEVICES ---
    const devices = {};

    // Helper: Create Simple Device
    function createBox(name, color, w, h, d, x, y, z) {
        const geo = new THREE.BoxGeometry(w, h, d);
        const mat = new THREE.MeshPhongMaterial({ color: color });
        const mesh = new THREE.Mesh(geo, mat);
        mesh.position.set(x, y + h/2, z);
        scene.add(mesh);
        devices[name] = mesh;
        return mesh;
    }

    // Lane
    const road = new THREE.Mesh(new THREE.PlaneGeometry(30, 6), new THREE.MeshPhongMaterial({ color: 0x1a1a1a }));
    road.rotation.x = -Math.PI / 2;
    road.position.z = 8;
    scene.add(road);

    // 1. Motorcycle
    const motorcycle = new THREE.Group();
    const body = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.8, 1.5), new THREE.MeshPhongMaterial({ color: 0xef4444 }));
    body.position.y = 0.6;
    motorcycle.add(body);
    const head = new THREE.Mesh(new THREE.SphereGeometry(0.2), new THREE.MeshPhongMaterial({ color: 0xffdbac }));
    head.position.y = 1.3;
    motorcycle.add(head);
    motorcycle.position.set(10, 0, 8); // Start position
    scene.add(motorcycle);
    devices.motorcycle = motorcycle;

    // 2. Barrier
    const barrierBase = createBox('barrier', 0xfacc15, 0.4, 1.2, 0.4, 0, 0, 10.5);
    const arm = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.2, 4), new THREE.MeshPhongMaterial({ color: 0xffffff }));
    arm.position.set(0, 1, -2);
    barrierBase.add(arm);
    devices.barrierArm = arm;

    // 3. RFID Reader
    createBox('rfid', 0x333333, 0.2, 1.3, 0.2, 2, 0, 10.5);

    // 4. Camera
    const camPole = createBox('camPole', 0x64748b, 0.1, 3, 0.1, 5, 0, 10.5);
    const camHead = createBox('camera', 0xffffff, 0.3, 0.2, 0.5, 5, 3, 10.5);
    camHead.rotation.x = 0.3;

    // 5. Loop Detector
    const loop = new THREE.Mesh(new THREE.PlaneGeometry(2, 1.5), new THREE.MeshBasicMaterial({ color: 0xef4444, transparent: true, opacity: 0.3 }));
    loop.rotation.x = -Math.PI / 2;
    loop.position.set(-5, 0.02, 8);
    scene.add(loop);
    devices.loop = loop;

    // 6. Station (PC & Switch)
    const desk = createBox('desk', 0x444444, 2, 0.8, 1, 0, 0, 0);
    const pc = createBox('pc', 0x111111, 0.6, 0.5, 0.1, 0, 0.8, -0.2); // Monitor
    const nvr = createBox('nvr', 0x334155, 0.6, 0.1, 0.4, -0.6, 0.8, 0.1); // Switch/NVR

    // --- CONNECTIONS (Lines) ---
    function createLink(start, end, color) {
        const points = [start, new THREE.Vector3(start.x, 0.1, start.z), new THREE.Vector3(end.x, 0.1, end.z), end];
        const curve = new THREE.CatmullRomCurve3(points);
        const geo = new THREE.TubeGeometry(curve, 20, 0.03, 8, false);
        const mat = new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: 0.4 });
        const mesh = new THREE.Mesh(geo, mat);
        scene.add(mesh);
        return { curve, mesh };
    }

    const links = {
        cam_nvr: createLink(new THREE.Vector3(5, 3, 10.5), new THREE.Vector3(-0.6, 0.85, 0.1), 0x3b82f6),
        nvr_pc: createLink(new THREE.Vector3(-0.6, 0.85, 0.1), new THREE.Vector3(0, 0.85, -0.2), 0x3b82f6),
        rfid_pc: createLink(new THREE.Vector3(2, 1.3, 10.5), new THREE.Vector3(0, 0.85, -0.2), 0xfacc15),
        pc_barrier: createLink(new THREE.Vector3(0, 0.85, -0.2), new THREE.Vector3(0, 0.5, 10.5), 0x22c55e),
        loop_barrier: createLink(new THREE.Vector3(-5, 0, 8), new THREE.Vector3(0, 0, 10.5), 0xef4444)
    };

    // --- DATA PACKET ANIMATION ---
    function sendPacket(linkKey, color, callback) {
        const link = links[linkKey];
        const packetGeo = new THREE.SphereGeometry(0.1);
        const packetMat = new THREE.MeshBasicMaterial({ color: color });
        const packet = new THREE.Mesh(packetGeo, packetMat);
        scene.add(packet);

        let t = 0;
        const interval = setInterval(() => {
            t += 0.02;
            if (t >= 1) {
                clearInterval(interval);
                scene.remove(packet);
                if (callback) callback();
            } else {
                const pos = link.curve.getPoint(t);
                packet.position.copy(pos);
            }
        }, 16);
    }

    // --- SIMULATION LOGIC ---
    let currentStep = 0;
    const steps = [
        { title: "BƯỚC 1: XE ĐẾN", desc: "Xe máy đi tới và dừng trước Barrier", action: () => moveMoto(2, 0) },
        { title: "BƯỚC 2: QUẸT THẺ", desc: "Người lái xe quẹt thẻ RFID lên đầu đọc", action: () => sendPacket('rfid_pc', 0xfacc15, nextStep) },
        { title: "BƯỚC 3: CHỤP ẢNH", desc: "Camera chụp ảnh và gửi dữ liệu về NVR/PC", action: () => {
            sendPacket('cam_nvr', 0x3b82f6, () => sendPacket('nvr_pc', 0x3b82f6, nextStep));
        }},
        { title: "BƯỚC 4: XỬ LÝ", desc: "Phần mềm kiểm tra dữ liệu và gửi lệnh mở Barrier", action: () => sendPacket('pc_barrier', 0x22c55e, () => openBarrier(true)) },
        { title: "BƯỚC 5: DI CHUYỂN", desc: "Xe máy đi qua Barrier", action: () => moveMoto(-5, 0) },
        { title: "BƯỚC 6: ĐÓNG BARRIER", desc: "Vòng từ phát hiện xe đã qua và đóng Barrier", action: () => {
            devices.loop.material.opacity = 1;
            sendPacket('loop_barrier', 0xef4444, () => {
                openBarrier(false);
                devices.loop.material.opacity = 0.3;
                setTimeout(resetSim, 2000);
            });
        }}
    ];

    function moveMoto(targetX, targetZ) {
        const startX = devices.motorcycle.position.x;
        let t = 0;
        const interval = setInterval(() => {
            t += 0.02;
            if (t >= 1) {
                clearInterval(interval);
                nextStep();
            } else {
                devices.motorcycle.position.x = startX + (targetX - startX) * t;
            }
        }, 16);
    }

    function openBarrier(open) {
        const targetRot = open ? Math.PI / 2 : 0;
        const startRot = devices.barrierArm.rotation.x;
        let t = 0;
        const interval = setInterval(() => {
            t += 0.05;
            if (t >= 1) {
                clearInterval(interval);
                if(open) nextStep();
            } else {
                devices.barrierArm.rotation.x = startRot + (targetRot - startRot) * t;
            }
        }, 16);
    }

    function nextStep() {
        if (currentStep < steps.length) {
            const step = steps[currentStep];
            document.getElementById('step-display').style.display = 'block';
            document.getElementById('step-title').innerText = step.title;
            document.getElementById('step-desc').innerText = step.desc;
            currentStep++;
            setTimeout(step.action, 1000);
        }
    }

    function resetSim() {
        currentStep = 0;
        devices.motorcycle.position.set(10, 0, 8);
        devices.barrierArm.rotation.x = 0;
        document.getElementById('step-display').style.display = 'none';
        document.getElementById('btn-play-flow').disabled = false;
    }

    document.getElementById('btn-play-flow').addEventListener('click', function() {
        this.disabled = true;
        nextStep();
    });

    // --- RENDER ---
    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });
</script>

<style>
    .btn-primary { background: #108042; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
    .btn-primary:hover:not(:disabled) { background: #0d6b35; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,128,66,0.3); }
    .btn-primary:disabled { background: #444; cursor: not-allowed; opacity: 0.7; }
</style>
