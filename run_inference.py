import os
import torch
import argparse
from transformers import Speech2TextTokenizer
from src.model.avhubert2text import AV2TextForConditionalGeneration
from src.dataset.load_data import load_feature

def load_model_and_tokenizer(model_name_or_path, cache_dir):
    # Load the model and tokenizer, ensuring the model is on the CPU
    model = AV2TextForConditionalGeneration.from_pretrained(model_name_or_path, cache_dir=cache_dir).eval()
    tokenizer = Speech2TextTokenizer.from_pretrained(model_name_or_path, cache_dir=cache_dir)
    return model, tokenizer

def run_inference(model, tokenizer, video_path, audio_path):
    # Load and process raw video and audio features
    sample = load_feature(video_path, audio_path)
    audio_feats = sample['audio_source']  # Keep tensors on the CPU
    video_feats = sample['video_source']  # Keep tensors on the CPU
    attention_mask = torch.BoolTensor(audio_feats.size(0), audio_feats.size(-1)).fill_(False)

    # Generate text
    output = model.generate(
        audio_feats,
        attention_mask=attention_mask,
        video=video_feats,
        max_length=1024,
    )

    # Decode the output
    pred_text = tokenizer.batch_decode(output, skip_special_tokens=True)
    return pred_text

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument('--model_name_or_path', type=str, required=True, help="Path or name of the pretrained model")
    parser.add_argument('--cache_dir', type=str, default="./model-bin", help="Directory to cache the model")
    parser.add_argument('--video_path', type=str, required=True, help="Path to the raw video file (lip movement)")
    parser.add_argument('--audio_path', type=str, required=True, help="Path to the raw audio file")
    args = parser.parse_args()

    # Load model and tokenizer
    model, tokenizer = load_model_and_tokenizer(args.model_name_or_path, args.cache_dir)

    # Run inference
    transcription = run_inference(model, tokenizer, args.video_path, args.audio_path)
    print(f"\nTranscription:\n{transcription}")