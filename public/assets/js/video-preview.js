/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */

(() => {
    'use strict';

    const initialized = new WeakSet();

    function frameFor(video) {
        return video.closest('[data-video-frame]') || video.parentElement;
    }

    function stageFor(video) {
        return video.closest('.story-video-stage') || frameFor(video);
    }

    function applyGeometry(video) {
        if (!video.videoWidth || !video.videoHeight) return;
        const frame = frameFor(video);
        const stage = stageFor(video);
        if (!frame || !stage) return;
        const ratio = video.videoWidth / video.videoHeight;
        [frame, stage].forEach(target => {
            target.style.setProperty('--video-aspect', `${video.videoWidth} / ${video.videoHeight}`);
            target.classList.toggle('is-video-portrait', ratio < 0.9);
            target.classList.toggle('is-video-square', ratio >= 0.9 && ratio <= 1.1);
            target.classList.toggle('is-video-wide', ratio > 1.45);
        });
    }

    function previewTime(video) {
        const duration = Number(video.duration || 0);
        if (!Number.isFinite(duration) || duration <= 0.15) return 0;
        return Math.min(2, Math.max(0.5, duration * 0.04), Math.max(0.05, duration - 0.05));
    }

    function cuePreview(video) {
        const time = previewTime(video);
        if (time <= 0) {
            video.classList.add('video-preview-ready');
            return;
        }
        const markReady = () => video.classList.add('video-preview-ready');
        video.addEventListener('seeked', markReady, { once: true });
        try {
            video.currentTime = time;
        } catch (_) {
            markReady();
        }
    }

    function generatePoster(video) {
        const frame = frameFor(video);
        const stage = stageFor(video);
        if (!frame || !stage || stage.querySelector('.generated-video-poster')) return;
        const time = previewTime(video);
        if (time <= 0) return;

        const capture = () => {
            try {
                const sourceW = video.videoWidth || 1280;
                const sourceH = video.videoHeight || 720;
                const maxW = 960;
                const scale = Math.min(1, maxW / sourceW);
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(sourceW * scale));
                canvas.height = Math.max(1, Math.round(sourceH * scale));
                const ctx = canvas.getContext('2d');
                if (!ctx) return;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const poster = document.createElement('img');
                poster.className = 'generated-video-poster';
                poster.alt = '';
                poster.setAttribute('aria-hidden', 'true');
                poster.src = canvas.toDataURL('image/jpeg', 0.82);
                stage.insertBefore(poster, video);
                const playButton = document.createElement('button');
                playButton.type = 'button';
                playButton.className = 'generated-video-play';
                playButton.setAttribute('aria-label', 'Play video');
                playButton.textContent = '▶';
                stage.appendChild(playButton);
                frame.classList.add('has-generated-video-poster');
                stage.classList.add('has-generated-video-poster');
                try { video.currentTime = 0; } catch (_) {}
                const dismiss = () => {
                    frame.classList.add('video-has-started');
                    stage.classList.add('video-has-started');
                };
                playButton.addEventListener('click', () => {
                    dismiss();
                    const result = video.play();
                    if (result && typeof result.catch === 'function') result.catch(() => {});
                });
                video.addEventListener('play', dismiss, { once: true });
            } catch (_) {
                // If the browser cannot capture a frame, the native player remains usable.
            }
        };

        video.addEventListener('seeked', capture, { once: true });
        try {
            video.currentTime = time;
        } catch (_) {}
    }

    function setup(video) {
        if (initialized.has(video)) return;
        initialized.add(video);

        const onMetadata = () => {
            applyGeometry(video);
            if (video.hasAttribute('data-video-preview')) cuePreview(video);
            if (video.hasAttribute('data-video-poster')) generatePoster(video);
        };

        video.addEventListener('error', () => frameFor(video)?.classList.add('video-preview-error'), { once: true });
        if (video.readyState >= 1) onMetadata();
        else video.addEventListener('loadedmetadata', onMetadata, { once: true });
    }

    function refresh(root = document) {
        if (root.matches?.('video[data-video-preview], video[data-video-poster]')) setup(root);
        root.querySelectorAll?.('video[data-video-preview], video[data-video-poster]').forEach(setup);
    }

    refresh(document);
    const observer = new MutationObserver(records => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (node.nodeType === 1) refresh(node);
            }
        }
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });

    window.USCVideoPreview = { refresh };
})();
