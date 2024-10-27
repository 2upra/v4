# hashAudio.py
import sys
import wave
import hashlib

def calcular_hash_audio(audio_path):
    try:
        # Abrir el archivo WAV
        with wave.open(audio_path, 'rb') as wav_file:
            # Leer todos los frames
            frames = wav_file.readframes(wav_file.getnframes())
            
            # Crear hash
            hash_obj = hashlib.sha256(frames)
            return hash_obj.hexdigest()
            
    except Exception as e:
        sys.stderr.write(f"Error procesando archivo: {str(e)}\n")
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.stderr.write("Error: Se requiere la ruta del archivo de audio\n")
        sys.exit(1)
    
    audio_path = sys.argv[1]
    hash_result = calcular_hash_audio(audio_path)
    
    if hash_result:
        print(hash_result)
    else:
        sys.exit(1)