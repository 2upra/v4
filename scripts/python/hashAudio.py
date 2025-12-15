import sys
import hashlib
import numpy as np
import librosa
import os

os.environ['NUMBA_CACHE_DIR'] = '/tmp'

def calcular_hash_audio(audio_path):
    try:
        # Reducir la duración del audio procesado
        duration = 30  # Solo procesar los primeros 30 segundos
        y, sr = librosa.load(audio_path, sr=22050, duration=duration)  # Reducir sample rate
        
        # Reducir la dimensionalidad de las características
        mfcc = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=13)  # Menos coeficientes
        chroma = librosa.feature.chroma_stft(y=y, sr=sr, n_chroma=6)  # Menos bandas cromáticas
        
        # Usar menos características
        features = np.concatenate([
            np.mean(mfcc, axis=1),
            np.mean(chroma, axis=1)
        ])
        
        features = np.round(features, 4)  # Menos precisión decimal
        return hashlib.sha256(features.tobytes()).hexdigest()
            
    except Exception as e:
        sys.stderr.write(f"Error: {str(e)}\n")
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit(1)
    
    hash_result = calcular_hash_audio(sys.argv[1])
    if hash_result:
        print(hash_result)
    else:
        sys.exit(1)