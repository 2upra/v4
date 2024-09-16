import essentia.standard as es
import json

def analizar_audio(audio_path):
    # Cargar el archivo de audio
    audio = es.MonoLoader(filename=audio_path)()

    # Extraer BPM
    rhythm_extractor = es.RhythmExtractor2013(method="multifeature")
    bpm, _, _, _, _ = rhythm_extractor(audio)

    # Extraer tono
    pitch_extractor = es.PredominantPitchMelodia()
    pitch, _ = pitch_extractor(audio)

    # Extraer tonalidad y escala
    key_extractor = es.KeyExtractor()
    key, scale, strength = key_extractor(audio)

    # Crear un diccionario con los resultados
    resultados = {
        "bpm": bpm,
        "pitch": pitch,
        "key": key,
        "scale": scale,
        "strength": strength
    }

    # Guardar los resultados en un archivo JSON
    with open(audio_path + '_resultados.json', 'w') as f:
        json.dump(resultados, f)

    return resultados

# Ejemplo de uso
if __name__ == "__main__":
    import sys
    audio_path = sys.argv[1]
    resultados = analizar_audio(audio_path)
    print(resultados)
