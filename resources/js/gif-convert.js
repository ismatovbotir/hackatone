import { GIFEncoder, quantize, applyPalette } from 'gifenc';

/**
 * Turn a short MP4 (an AI draft on our own server) into a small looping GIF in the admin's browser.
 * Shared hosting usually has no ffmpeg, so the conversion happens here and the GIF is uploaded.
 * One palette for all frames keeps colours stable and the file small. If the result is bigger than
 * maxBytes, it tries again smaller.
 */
const attempts = [
    { width: 320, fps: 8, colors: 64 },
    { width: 256, fps: 6, colors: 48 },
    { width: 200, fps: 5, colors: 32 },
];

export async function mp4ToGif(src, { maxSeconds = 4, maxBytes = 2 * 1024 * 1024, onProgress = () => {} } = {}) {
    const video = document.createElement('video');
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.src = src;
    await new Promise((resolve, reject) => {
        video.onloadeddata = resolve;
        video.onerror = () => reject(new Error('The video could not be loaded.'));
    });

    const duration = Math.min(video.duration || maxSeconds, maxSeconds);
    let blob = null;

    for (const [i, a] of attempts.entries()) {
        blob = await encode(video, duration, a, (p) => onProgress((i + p) / attempts.length));
        if (blob.size <= maxBytes) {
            onProgress(1);
            return blob;
        }
    }

    throw new Error('The GIF is still too large. Try a shorter clip.');
}

async function encode(video, duration, { width, fps, colors }, onProgress) {
    const height = Math.round((video.videoHeight / video.videoWidth) * width / 2) * 2;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    const count = Math.max(2, Math.floor(duration * fps));
    const frames = [];
    for (let f = 0; f < count; f++) {
        await seek(video, Math.min(duration - 0.01, f / fps));
        ctx.drawImage(video, 0, 0, width, height);
        frames.push(ctx.getImageData(0, 0, width, height).data);
        onProgress((f + 1) / count * 0.7);
    }

    // One palette from a sample of frames (first, middle, last).
    const sample = [frames[0], frames[Math.floor(count / 2)], frames[count - 1]];
    const joined = new Uint8ClampedArray(sample.reduce((n, d) => n + d.length, 0));
    sample.reduce((offset, d) => (joined.set(d, offset), offset + d.length), 0);
    const palette = quantize(joined, colors);

    const gif = GIFEncoder();
    frames.forEach((data, f) => {
        gif.writeFrame(applyPalette(data, palette), width, height, { palette: f === 0 ? palette : undefined, delay: Math.round(1000 / fps), repeat: 0 });
        onProgress(0.7 + (f + 1) / count * 0.3);
    });
    gif.finish();

    return new Blob([gif.bytes()], { type: 'image/gif' });
}

function seek(video, time) {
    return new Promise((resolve) => {
        video.onseeked = () => resolve();
        video.currentTime = time;
    });
}
