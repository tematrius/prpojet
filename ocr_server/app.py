from flask import Flask, request, jsonify
import tempfile
import os
import pytesseract
import fitz  # PyMuPDF
from pdf2image import convert_from_path
from PIL import Image, ImageOps



app = Flask(__name__)

@app.route("/ocr", methods=["POST"])
def traiter_ocr():
    if 'file' not in request.files:
        return jsonify({"erreur": "Aucun fichier reçu."}), 400

    fichier = request.files['file']
    provenance = request.form.get('provenance', 'Inconnue')

    with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp_pdf:
        fichier.save(tmp_pdf.name)

    try:
        texte_final = ""

        # 1. Tenter extraction directe (PDF numérique)
        doc = fitz.open(tmp_pdf.name)
        for page in doc:
            texte_final += page.get_text()

        doc.close()

        # 2. Si le texte est vide, fallback OCR
        if not texte_final.strip():
            print("OCR fallback triggered")
            images = convert_from_path(tmp_pdf.name, dpi=200, poppler_path=r'C:\poppler-24.08.0\Library\bin')
            print(f"Nombre d'images extraites : {len(images)}")
            for idx, image in enumerate(images):
                gray = ImageOps.grayscale(image)
                # Binarisation pour améliorer la reconnaissance
                bw = gray.point(lambda x: 0 if x < 180 else 255, '1')
                texte = pytesseract.image_to_string(bw, lang='fra+eng', config='--psm 3')
                print(f"OCR page {idx+1}: {texte[:100]}")  # Affiche les 100 premiers caractères de chaque page OCR"
                texte_final += texte + "\n"

        if not texte_final.strip():
            return jsonify({"erreur": "Aucun texte détecté même après OCR."}), 422

        return jsonify({
            "contenu": texte_final.strip(),
            "provenance": provenance
        })

    except Exception as e:
        print("Erreur serveur OCR :", str(e))
        return jsonify({"erreur": str(e)}), 500
    finally:
        os.unlink(tmp_pdf.name)

if __name__ == "__main__":
    app.run(debug=True)
