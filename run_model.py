import sys
import subprocess
from pathlib import Path
import glob

# Read arguments passed from PHP
video_path = Path(sys.argv[1])  # Full path to uploaded video 
output_txt = Path(sys.argv[2])  # Full path to output transcript.txt
output_dir = video_path.parent

venv_python = Path("venv/Scripts/python.exe")

# STEP 1: Preprocessing
preprocess = subprocess.run([
    str(venv_python),
    "AV-HuBERT-S2S/src/dataset/video_to_audio_lips.py",
    "--input_path", str(video_path),
    "--output_path", str(output_dir)
], capture_output=True, text=True)

print("=== PREPROCESS STDOUT ===")
print(preprocess.stdout)
print("=== PREPROCESS STDERR ===")
print(preprocess.stderr)

if preprocess.returncode != 0:
    print("Preprocessing failed")
    sys.exit(1)

# STEP 2: Locate files
# Find the newest noisy audio
noisy_audio_files = sorted(
    output_dir.glob(f"{video_path.stem}_noisy_audio_*.wav"),
    key=lambda x: x.stat().st_mtime,
    reverse=True
)

if not noisy_audio_files:
    print("No noisy audio found.")
    sys.exit(1)

audio_path = noisy_audio_files[0]
lips_path = output_dir / f"{video_path.stem}_lip_movement.mp4"

# STEP 3: Inference
inference = subprocess.run([
    str(venv_python),
    "AV-HuBERT-S2S/run_inference.py",
    "--model_name_or_path", "nguyenvulebinh/AV-HuBERT-MuAViC-en",
    "--video_path", str(lips_path),
    "--audio_path", str(audio_path)
], capture_output=True, text=True)

print("=== INFERENCE STDOUT ===")
print(inference.stdout)
print("=== INFERENCE STDERR ===")
print(inference.stderr)

if inference.returncode != 0:
    print("Inference failed")
    sys.exit(1)


# STEP 4: Save transcript
for line in reversed(inference.stdout.strip().splitlines()):
    if line.strip():
        output_txt.write_text(line.strip(), encoding="utf-8")
        print(f"Transcript saved to: {output_txt}")
        break
