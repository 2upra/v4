import essentia.standard as es
import json
import numpy as np

# Lista de géneros (ejemplo con los primeros 10)
GENRES = [
        "Abstract", "Acid", "Acid House", "Acid Jazz", "Ambient", "Bassline", "Beatdown", "Berlin-School", "Big Beat", "Bleep", "Breakbeat", "Breakcore", "Breaks", "Broken Beat", "Chillwave", "Chiptune", "Dance-pop", "Dark Ambient", "Darkwave", "Deep House", "Deep Techno", "Disco", "Disco Polo", "Donk", "Downtempo", "Drone", "Drum n Bass", "Dub", "Dub Techno", "Dubstep", "Dungeon Synth", "EBM", "Electro", "Electro House", "Electroclash", "Euro House", "Euro-Disco", "Eurobeat", "Eurodance", "Experimental", "Freestyle", "Future Jazz", "Gabber", "Garage House", "Ghetto", "Ghetto House", "Glitch", "Goa Trance", "Grime", "Halftime", "Hands Up", "Happy Hardcore", "Hard House", "Hard Techno", "Hard Trance", "Hardcore", "Hardstyle", "Hi NRG", "Hip Hop", "Hip-House", "House", "IDM", "Illbient", "Industrial", "Italo House", "Italo-Disco", "Italodance", "Jazzdance", "Juke", "Jumpstyle", "Jungle", "Latin", "Leftfield", "Makina", "Minimal", "Minimal Techno", "Modern Classical", "Musique Concrète", "Neofolk", "New Age", "New Beat", "New Wave", "Noise", "Nu-Disco", "Power Electronics", "Progressive Breaks", "Progressive House", "Progressive Trance", "Psy-Trance", "Rhythmic Noise", "Schranz", "Sound Collage", "Speed Garage", "Speedcore", "Synth-pop", "Synthwave", "Tech House", "Tech Trance", "Techno", "Trance", "Tribal", "Tribal House", "Trip Hop", "Tropical House", "UK Garage", "Vaporwave"
        "Folk, World, & Country", "African", "Bluegrass", "Cajun", "Canzone Napoletana", "Catalan Music", "Celtic", "Country", "Fado", "Flamenco", "Folk", "Gospel", "Highlife", "Hillbilly", "Hindustani", "Honky Tonk", "Indian Classical", "Laïkó", "Nordic", "Pacific", "Polka", "Raï", "Romani", "Soukous", "Séga", "Volksmusik", "Zouk", "Éntekhno"
        "Funk / Soul", "Afrobeat", "Boogie", "Contemporary R&B", "Disco", "Free Funk", "Funk", "Gospel", "Neo Soul", "New Jack Swing", "P.Funk", "Psychedelic", "Rhythm & Blues", "Soul", "Swingbeat", "UK Street Soul"
        "Hip Hop", "Bass Music", "Boom Bap", "Bounce", "Britcore", "Cloud Rap", "Conscious", "Crunk", "Cut-up/DJ", "DJ Battle Tool", "Electro", "G-Funk", "Gangsta", "Grime", "Hardcore Hip-Hop", "Horrorcore", "Instrumental", "Jazzy Hip-Hop", "Miami Bass", "Pop Rap", "Ragga HipHop", "RnB/Swing", "Screw", "Thug Rap", "Trap", "Trip Hop", "Turntablism"
        "Jazz", "Afro-Cuban Jazz", "Afrobeat", "Avant-garde Jazz", "Big Band", "Bop", "Bossa Nova", "Contemporary Jazz", "Cool Jazz", "Dixieland", "Easy Listening", "Free Improvisation", "Free Jazz", "Fusion", "Gypsy Jazz", "Hard Bop", "Jazz-Funk", "Jazz-Rock", "Latin Jazz", "Modal", "Post Bop", "Ragtime", "Smooth Jazz", "Soul-Jazz", "Space-Age", "Swing"
        "Latin", "Afro-Cuban", "Baião", "Batucada", "Beguine", "Bolero", "Boogaloo", "Bossanova", "Cha-Cha", "Charanga", "Compas", "Cubano", "Cumbia", "Descarga", "Forró", "Guaguancó", "Guajira", "Guaracha", "MPB", "Mambo", "Mariachi", "Merengue", "Norteño", "Nueva Cancion", "Pachanga", "Porro", "Ranchera", "Reggaeton", "Rumba", "Salsa", "Samba", "Son", "Son Montuno", "Tango", "Tejano", "Vallenato"
        "Non-Music", "Audiobook", "Comedy", "Dialogue", "Education", "Field Recording", "Interview", "Monolog", "Poetry", "Political", "Promotional", "Radioplay", "Religious", "Spoken Word"
        "Pop", "Ballad", "Bollywood", "Bubblegum", "Chanson", "City Pop", "Europop", "Indie Pop", "J-pop", "K-pop", "Kayōkyoku", "Light Music", "Music Hall", "Novelty", "Parody", "Schlager", "Vocal"
        "Reggae", "Calypso", "Dancehall", "Dub", "Lovers Rock", "Ragga", "Reggae", "Reggae-Pop", "Rocksteady", "Roots Reggae", "Ska", "Soca"
        "Rock", "AOR", "Acid Rock", "Acoustic", "Alternative Rock", "Arena Rock", "Art Rock", "Atmospheric Black Metal", "Avantgarde", "Beat", "Black Metal", "Blues Rock", "Brit Pop", "Classic Rock", "Coldwave", "Country Rock", "Crust", "Death Metal", "Deathcore", "Deathrock", "Depressive Black Metal", "Doo Wop", "Doom Metal", "Dream Pop", "Emo", "Ethereal", "Experimental", "Folk Metal", "Folk Rock", "Funeral Doom Metal", "Funk Metal", "Garage Rock", "Glam", "Goregrind", "Goth Rock", "Gothic Metal", "Grindcore", "Grunge", "Hard Rock", "Hardcore", "Heavy Metal", "Indie Rock", "Industrial", "Krautrock", "Lo-Fi", "Lounge", "Math Rock", "Melodic Death Metal", "Melodic Hardcore", "Metalcore", "Mod", "Neofolk", "New Wave", "No Wave", "Noise", "Noisecore", "Nu Metal", "Oi", "Parody", "Pop Punk", "Pop Rock", "Pornogrind", "Post Rock", "Post-Hardcore", "Post-Metal", "Post-Punk", "Power Metal", "Power Pop", "Power Violence", "Prog Rock", "Progressive Metal", "Psychedelic Rock", "Psychobilly", "Pub Rock", "Punk", "Rock & Roll", "Rockabilly", "Shoegaze", "Ska", "Sludge Metal", "Soft Rock", "Southern Rock", "Space Rock", "Speed Metal", "Stoner Rock", "Surf", "Symphonic Rock", "Technical Death Metal", "Thrash", "Twist", "Viking Metal", "Yé-Yé"
        "Stage & Screen", "Musical", "Score", "Soundtrack", "Theme"
]

def analizar_audio(audio_path):
    try:
        # Cargar el audio a 16kHz como requiere el modelo
        audio = es.MonoLoader(filename=audio_path, sampleRate=16000, resampleQuality=4)()
        print(f"Audio cargado correctamente. Longitud: {len(audio)} muestras.")

        # Aplicar TensorflowPredictEffnetDiscogs para obtener embeddings
        embedding_model = es.TensorflowPredictEffnetDiscogs(graphFilename="discogs-effnet-bs64-1.pb", output="PartitionedCall:1")
        embeddings = embedding_model(audio)

        # Aplicar el modelo de género
        genre_model = es.TensorflowPredict2D(graphFilename="genre_discogs400-discogs-effnet-1.pb", 
                                             input="serving_default_model_Placeholder", 
                                             output="PartitionedCall:0")
        genre_predictions = genre_model(embeddings)

        # Calcular el promedio de las predicciones de género
        avg_genre_predictions = np.mean(genre_predictions, axis=0)

        # Obtener los 5 géneros más probables
        top_5_indices = np.argsort(avg_genre_predictions)[-5:][::-1]
        top_5_genres = [GENRES[i] for i in top_5_indices]
        top_5_probs = avg_genre_predictions[top_5_indices]

        print("Top 5 géneros detectados:")
        for genre, prob in zip(top_5_genres, top_5_probs):
            print(f"{genre}: {prob:.4f}")

        # Extraer BPM
        rhythm_extractor = es.RhythmExtractor2013(method="multifeature")
        bpm, _, _, _, _ = rhythm_extractor(audio)
        print(f"BPM detectado: {bpm}")

        # Extraer tono
        pitch_extractor = es.PredominantPitchMelodia()
        pitch, _ = pitch_extractor(audio)
        print(f"Pitch (tono) detectado: {pitch[:10]}")

        # Otros datos relevantes
        key_extractor = es.KeyExtractor()
        key, scale, strength = key_extractor(audio)
        print(f"Clave detectada: {key}, Escala: {scale}, Fuerza: {strength}")

        # Crear un diccionario con los resultados
        resultados = {
            "bpm": bpm,
            "pitch": pitch.tolist(),
            "key": key,
            "scale": scale,
            "strength": strength,
            "top_5_genres": [{"genre": g, "probability": float(p)} for g, p in zip(top_5_genres, top_5_probs)]
        }

        # Guardar los resultados en un archivo JSON
        with open(audio_path + '_resultados.json', 'w') as f:
            json.dump(resultados, f)
        print("Archivo JSON guardado correctamente.")

        return resultados

    except Exception as e:
        print(f"Error durante el análisis del audio: {e}")

# Ejemplo de uso
if __name__ == "__main__":  
    import sys
    audio_path = sys.argv[1]
    resultados = analizar_audio(audio_path)
    print(resultados)