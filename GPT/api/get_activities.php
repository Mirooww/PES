<?php
// api/get_activities.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Ajustez selon vos besoins de sécurité
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Inclure le fichier de configuration
require_once('../config/config.php');

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Méthode non autorisée"]);
    exit();
}

// Récupérer les données JSON de la requête
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Données invalides"]);
    exit();
}

// Récupérer la position géolocalisée de l'utilisateur
$userLatitude = isset($input['latitude']) ? $input['latitude'] : null;
$userLongitude = isset($input['longitude']) ? $input['longitude'] : null;

if ($userLatitude === null || $userLongitude === null) {
    http_response_code(400);
    echo json_encode(["error" => "La position de l'utilisateur est manquante."]);
    exit();
}

// Construire le prompt basé sur les capacités de l'utilisateur
$capabilities = [];
if (!empty($input['pmr'])) {
    $capabilities[] = "personne à mobilité réduite (PMR)";
}
if (!empty($input['easily_fatigued'])) {
    $capabilities[] = "facilement fatigue";
}
if (!empty($input['valid'])) {
    $capabilities[] = "valide";
}

if (empty($capabilities)) {
    http_response_code(400);
    echo json_encode(["error" => "Aucune capacité sélectionnée"]);
    exit();
}

$capabilities_text = implode(", ", $capabilities);

// Créer le prompt pour l'API OpenAI
$prompt = "Génère une liste de 5 activités adaptées pour une personne qui est $capabilities_text se trouvant à la latitude $userLatitude et la longitude $userLongitude. Retourne les résultats au format JSON avec un tableau nommé 'activities' contenant pour chaque activité les champs suivants :
- 'name' : nom de l'activité
- 'description' : description de l'activité
- 'latitude' et 'longitude' : coordonnées géographiques de l'activité proches de la position de l'utilisateur
- 'opening_hours' : horaires d'ouverture (si disponibles)
- 'booking_link' : URL pour la réservation en ligne (si disponible)
- 'phone_number' : numéro de téléphone (si disponible)
Assure-toi que la sortie est uniquement le JSON sans texte supplémentaire.";

// Limiter le nombre de tokens pour éviter les réponses trop longues
$maxTokens = 1500;

$apiUrl = 'https://api.openai.com/v1/chat/completions';

// Préparer les données de la requête
$requestData = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        [
            "role" => "system",
            "content" => "Tu es un assistant qui aide à trouver des activités adaptées."
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0.7,
    "max_tokens" => $maxTokens,
    "n" => 1,
    "stop" => null,
];

// Initialiser cURL
$ch = curl_init($apiUrl);

// Configurer les options de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $_ENV['OPENAI_API_KEY']
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

// Exécuter la requête
$response = curl_exec($ch);

if ($response === false) {
    $error_msg = curl_error($ch);
    http_response_code(500);
    echo json_encode(["error" => "Erreur cURL 2", "details" => $error_msg]);
    curl_close($ch);
    exit();
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Vérifier le code de réponse HTTP
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(["error" => "Erreur lors de la récupération des activités", "details" => $response]);
    exit();
}

// Traiter la réponse de l'API
$apiResponse = json_decode($response, true);

if (isset($apiResponse['error'])) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de l'API", "details" => $apiResponse['error']]);
    exit();
}

// Extraire le contenu généré
if (isset($apiResponse['choices'][0]['message']['content'])) {
    $assistantReply = $apiResponse['choices'][0]['message']['content'];
} else {
    http_response_code(500);
    echo json_encode(["error" => "Réponse inattendue de l'API", "details" => $apiResponse]);
    exit();
}

// Tenter de parser le JSON généré
$suggestions = json_decode($assistantReply, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Si le JSON n'est pas valide, renvoyer une erreur
    http_response_code(500);
    echo json_encode([
        "error" => "L'API n'a pas renvoyé un JSON valide.",
        "details" => $assistantReply
    ]);
    exit();
}

// Vérifier que les activités sont présentes
if (!isset($suggestions['activities']) || !is_array($suggestions['activities'])) {
    http_response_code(500);
    echo json_encode(["error" => "Format des activités invalide.", "details" => $suggestions]);
    exit();
}

// Retourner les suggestions au frontend
echo json_encode($suggestions);
