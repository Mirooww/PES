<?php
// api/getActivities.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once('config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Données invalides"]);
    exit();
}

$userLatitude = $input['latitude'] ?? null;
$userLongitude = $input['longitude'] ?? null;
$north = $input['north'] ?? null;
$south = $input['south'] ?? null;
$east = $input['east'] ?? null;
$west = $input['west'] ?? null;

$userInfo = $input['userInfo'] ?? [];
$ability = $userInfo['ability'] ?? "aucune capacité spécifiée";
$activityPreferences = $userInfo['activityPreferences'] ?? null;

$preferencesText = $activityPreferences ? ", avec des préférences pour les activités suivantes : $activityPreferences" : "";

// Construire le prompt pour l'API Gemini
$prompt = "Génère une liste de 5 activités adaptées pour une personne ayant la capacité \"$ability\", se trouvant à la latitude $userLatitude et la longitude $userLongitude$preferencesText. Retourne les résultats au format JSON avec un tableau nommé 'activities' contenant pour chaque activité les champs suivants :
- 'name' : nom de l'activité
- 'description' : description de l'activité
- 'lat' et 'lng' : coordonnées géographiques de l'activité proches de la position de l'utilisateur
- 'opening_hours' : horaires d'ouverture (si disponibles)
- 'booking_link' : URL pour la réservation en ligne (si disponible)
- 'phone_number' : numéro de téléphone (si disponible)
Assure-toi que la sortie est uniquement le JSON sans texte supplémentaire.";

// Clé API Gemini
$apiKey = $_ENV['GOOGLE_API_KEY'];

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "Clé API manquante"]);
    exit();
}

$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;

// Construire la requête
$requestData = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => $prompt
                ]
            ]
        ]
    ]
];

// Initialiser cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur cURL", "details" => curl_error($ch)]);
    curl_close($ch);
    exit();
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(["error" => "Erreur lors de la récupération des activités", "details" => $response]);
    exit();
}

$apiResponse = json_decode($response, true);

if (isset($apiResponse['error'])) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de l'API", "details" => $apiResponse['error']]);
    exit();
}

$assistantReply = $apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$assistantReply) {
    http_response_code(500);
    echo json_encode(["error" => "Réponse vide ou inattendue de Gemini", "details" => $apiResponse]);
    exit();
}

// Extraire uniquement le JSON entre les balises ```json ... ``` (si présent)
if (preg_match('/```json\s*(.*?)\s*```/s', $assistantReply, $matches)) {
    $jsonBlock = $matches[1];
} else {
    // Tentative de fallback : peut-être que Gemini renvoie directement du JSON sans balises
    $jsonBlock = trim($assistantReply);
}

$suggestions = json_decode($jsonBlock, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        "error" => "Le bloc JSON extrait est invalide",
        "details" => $jsonBlock,
        "json_last_error" => json_last_error_msg()
    ]);
    exit();
}

if (!isset($suggestions['activities']) || !is_array($suggestions['activities'])) {
    http_response_code(500);
    echo json_encode(["error" => "Format des activités invalide", "details" => $suggestions]);
    exit();
}

echo json_encode($suggestions);
exit();
?>
