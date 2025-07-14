<?php
namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
class CityService
{
    /**
     * Find or create a city with its coordinates
     */
    public function findOrCreateCity(string $cityState): ?City
    {
        // Parse do formato "Curitiba - PR"
        $parts = explode('-', $cityState);
        if (count($parts) !== 2) {
            return null;
        }
        
        $cityName = trim($parts[0]);
        $state = trim($parts[1]);
        
        // Busca na tabela local primeiro
        $city = City::where('name', $cityName)
                      ->where('state', $state)
                      ->first();
        
        if ($city) {
            return $city;
        }
        
        // Se não encontrou, busca na API externa
        $coordinates = $this->fetchCoordinatesFromAPI($cityName, $state);
        
        if ($coordinates) {
   
            return City::create([
                'name' => $cityName,
                'state' => $state,
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng']
            ]);
        }
        
        return null;
    }
    
    /**
     * Fetch coordinates from external API (example using OpenCage or Google)
     */
    private function fetchCoordinatesFromAPI(string $city, string $state): ?array
    {
        try {
            // Exemplo usando OpenCage Geocoding API (gratuita)
            $apiKey = env('OPENCAGE_API_KEY');
            $query = urlencode("$city, $state, Brasil");
            
            $url = "https://api.opencagedata.com/geocode/v1/json?q={$query}&key={$apiKey}&limit=1";
            
            $response = Http::timeout(10)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['results'])) {
                    $result = $data['results'][0];
                    
                    return [
                        'lat' => $result['geometry']['lat'],
                        'lng' => $result['geometry']['lng']
                    ];
                }
            }
            
            // Fallback para ViaCEP + Nominatim (alternativa gratuita)
            return $this->fetchAlternativeCoordinates($city, $state);
            
        } catch (\Exception $e) {
            \Log::error("Erro ao buscar coordenadas: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Alternative method using Nominatim (OpenStreetMap)
     */
    private function fetchAlternativeCoordinates(string $city, string $state): ?array
    {
        try {
            $query = urlencode("$city, $state, Brasil");
            $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&limit=1";
            
            $response = Http::timeout(10)
                           ->withHeaders(['User-Agent' => 'SeuApp/1.0'])
                           ->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data)) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lng' => (float) $data[0]['lon']
                    ];
                }
            }
            
        } catch (\Exception $e) {
            \Log::error("Erro ao buscar coordenadas alternativa: " . $e->getMessage());
        }
        
        return null;
    }
}