# hashAudio.py
import sys
import hashlib
import soundfile as sf
import numpy as np

def calcular_hash_audio(audio_path):
    try:
        # Leer el archivo de audio usando soundfile
        data, samplerate = sf.read(audio_path)
        
        # Convertir el array a bytes
        audio_bytes = data.tobytes()
        
        # Crear hash
        hash_obj = hashlib.sha256(audio_bytes)
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