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

    const response = await fetch('http://localhost:8738/getActivitiesGoogle.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestBody),
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`Erreur API : ${response.status} - ${errorText}`);
    }

    const data: ApiResponse = await response.json();

    if (!data.activities || !Array.isArray(data.activities)) {
      console.error("Réponse inattendue de l'API : ", data);
      return null;
    }

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
