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
            images = convert_from_path(tmp_pdf.name, dpi=300)
            for image in images:
                gray = ImageOps.grayscale(image)
                # Optional enhancement: thresholding, denoising etc.
                texte = pytesseract.image_to_string(gray, lang='fra+eng', config='--psm 6')
                texte_final += texte + "\n"

        if not texte_final.strip():
            return jsonify({"erreur": "Aucun texte détecté même après OCR."}), 422

        return jsonify({
            "contenu": texte_final.strip(),
            "provenance": provenance
        })

    except Exception as e:
        return jsonify({"erreur": str(e)}), 500
    finally:
        os.unlink(tmp_pdf.name)

if __name__ == "__main__":
    app.run(debug=True)
