// api/server.js
import express from "express";
import axios from "axios";
import cors from "cors";
import dotenv from "dotenv";

dotenv.config();

const app = express();
const PORT = 3002;

app.use(cors({
  origin: '*',
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));

app.use(express.json());

const openaiApiKey = process.env.VITE_OPENAI_API_KEY;

if (!openaiApiKey) {
  console.error(
    "Erreur : OPENAI_API_KEY n'est pas définie dans le fichier .env"
  );
  process.exit(1);
}

app.post("/api/suggestions", async (req, res) => {
  const { latitude, longitude, north, south, east, west, userInfo } = req.body;

  if (!latitude || !longitude || !north || !south || !east || !west) {
    console.error("Erreur : Latitude ou longitude manquante dans la requête");
    return res.status(400).json({
      error:
        "Latitude, longitude, et les limites de la carte (north, south, east, west) sont requis.",
    });
  }

  console.log(
    `Requête reçue pour les coordonnées : latitude=${latitude}, longitude=${longitude}`
  );

  // Construire le message utilisateur pour OpenAI en incluant les informations utilisateur
  const userMessageContent = `Génère une liste de 5 activités adaptées pour une personne ayant la capacité "${
    userInfo?.ability
  }", 
    se trouvant à la latitude ${latitude} et la longitude ${longitude}${
    userInfo?.activityPreferences
      ? `, avec des préférences pour les activités suivantes : ${userInfo.activityPreferences}`
      : ""
  }. Retourne les résultats au format JSON avec un tableau nommé 'activities' contenant pour chaque activité les champs suivants :
  - 'name' : nom de l'activité
  - 'description' : description de l'activité
  - 'lat' et 'lng' : coordonnées géographiques de l'activité proches de la position de l'utilisateur
  - 'opening_hours' : horaires d'ouverture (si disponibles)
  - 'booking_link' : URL pour la réservation en ligne (si disponible)
  - 'phone_number' : numéro de téléphone (si disponible)
  Assure-toi que la sortie est uniquement le JSON sans texte supplémentaire.`;

  const messages = [
    {
      role: "system",
      content: "Tu es un assistant qui aide à trouver des activités adaptées.",
    },
    {
      role: "user",
      content: userMessageContent,
    },
  ];

  try {
    console.log("Envoi de la requête à l'API OpenAI...");

    const response = await axios.post(
      "https://api.openai.com/v1/chat/completions",
      {
        model: "gpt-3.5-turbo",
        messages: messages,
        max_tokens: 1500,
        temperature: 0.7,
      },
      {
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${openaiApiKey}`,
        },
      }
    );

    console.log("Réponse reçue de l'API OpenAI");

    const openaiResponseText =
      response.data.choices[0]?.message?.content?.trim();
    if (!openaiResponseText) {
      console.error("Erreur : Réponse vide ou mal formée de l'API OpenAI");
      return res.status(500).json({
        error: "Erreur lors de la génération des suggestions d'activités",
      });
    }

    console.log("Réponse brute de l'API OpenAI:", openaiResponseText);

    let activitiesData;
    try {
      activitiesData = JSON.parse(openaiResponseText);
    } catch (jsonError) {
      console.error("Erreur lors du parsing JSON :", jsonError);
      console.error("Texte retourné par OpenAI :", openaiResponseText);
      return res.status(500).json({
        error: "Erreur de formatage des données reçues de l'API OpenAI",
      });
    }

    res.json(activitiesData);
  } catch (error) {
    if (error.response) {
      console.error(
        "Erreur de réponse de l'API OpenAI :",
        error.response.status,
        error.response.data
      );
      res.status(error.response.status).json({ error: error.response.data });
    } else if (error.request) {
      console.error(
        "Erreur de requête envoyée, mais sans réponse reçue :",
        error.request
      );
      res.status(500).json({ error: "Pas de réponse de l'API OpenAI" });
    } else {
      console.error(
        "Erreur lors de la requête à l'API OpenAI :",
        error.message
      );
      res.status(500).json({
        error: "Erreur lors de la génération des suggestions d'activités",
      });
    }
  }
});

app.listen(PORT, () => {
  console.log(`Serveur API démarré sur http://localhost:${PORT}`);
});
