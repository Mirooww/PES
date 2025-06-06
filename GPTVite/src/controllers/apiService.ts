import { Activity, ApiResponse } from '../types';

interface UserInfo {
  name: string;
  activityPreferences: string;
}

export const fetchActivities = async (
  latitude: number,
  longitude: number,
  bounds?: { north: number; south: number; east: number; west: number },
  userInfo?: UserInfo
): Promise<Activity[] | null> => {
  try {
    if (!bounds) {
      console.error("Les limites de la carte (bounds) ne sont pas définies.");
      return null;
    }

    const requestBody = {
      latitude,
      longitude,
      north: bounds.north,
      south: bounds.south,
      east: bounds.east,
      west: bounds.west,
      userInfo,
    };

    const response = await fetch('http://localhost:8738/getActivities.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestBody),
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error("Erreur lors de la récuzdv;,ùopmzNFIOLZEHIOMEZFHEIO9PMZpération des données de l'API : " + errorText);
    }

    // Étape 1: Obtenir la réponse JSON complète
    const responseJson = await response.json();

    // Vérifier s'il y a une erreur dans la réponse
    if (responseJson.error) {
      console.error("Erreur API : ", responseJson.error);
      return null;
    }

    // Étape 2: Naviguer jusqu'à la propriété 'text'
    const textContent = responseJson.details.candidates[0].content.parts[0].text;

    // Étape 3: Nettoyer la chaîne JSON
    const jsonString = textContent.replace(/^```json\s*/, '').replace(/```$/, '').trim();

    // Étape 4: Analyser la chaîne JSON
    const data: ApiResponse = JSON.parse(jsonString);

    return data.activities.map((activity) => ({
      ...activity,
      lat: activity.lat,
      lng: activity.lng,
    }));
  } catch (error) {
    console.error("Erreur API : ", error);
    return null;
  }
};

