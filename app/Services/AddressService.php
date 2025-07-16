<?php
namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
class AddressService
{
    /**
     * Find or create a city with its coordinates
     */
    public function findOrCreateAddress(array $address): ?Address
    {      
        Log::info('in', $address);
        // Busca na tabela local primeiro
        $Address = Address::where('cep', $address['cep'])
                      ->where('number', $address['number'])
                      ->first();
        
        if ($Address) {
            return $Address;
        }

        $Address = Address::where('street', $address['street'])
        ->where('number', $address['number'])
        ->where('state', $address['state'])
        ->first();

        if ($Address) {
            return $Address;
        }

        $Address = Address::where('city', $address['city'])
        ->where('state', $address['state'])
        ->first();

        if ($Address) {
            return $Address;
        }

        // Se não encontrou, busca na API externa
        $search = '';

        if($address['street']){
            $search .= $address['street'].', ';
        }
        if($address['number']){
            $search .= $address['number'].', ';
        }
        if($address['cep']){
            $search .= $address['cep'].', ';
        } 
        if($address['city'] && $address['state'] ){
            $search .= $address['city'] . ', '. $address['state'].', ';
        } 

        if($search){
            
            $search .= ", Brasil";

            $coordinates = $this->fetchCoordinatesFromAddress($search);
            
            if ($coordinates) {   
                return Address::create([
                    'city' => $address['city'],
                    'cep' => $address['cep'],
                    'number' => $address['number'],
                    'street' => $address['street'],
                    'state' => $address['state'],
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng']
                ]);
            }
        }
        
        return null;
    }
    
   
    private function fetchCoordinatesFromAddress($search): ?array
    {
        try {
            // Monta o endereço completo
             
            // Exemplo usando OpenCage Geocoding API
            $apiKey = env('OPENCAGE_API_KEY');
            $query = urlencode($search);
            
            $url = "https://api.opencagedata.com/geocode/v1/json?q={$query}&key={$apiKey}&limit=1&countrycode=br";
            Log::info('import', [$url]);

            $response = Http::timeout(10)->get($url);
            Log::info("response", [$response]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['results'])) {
                    $result = $data['results'][0];
                    
                    return [
                        'lat' => $result['geometry']['lat'],
                        'lng' => $result['geometry']['lng'],
                        'formatted_address' => $result['formatted'] ?? $address,
                        'confidence' => $result['confidence'] ?? 0
                    ];
                }
            }
            
            // Fallback para Google Maps Geocoding API
            // return $this->fetchGoogleMapsCoordinates($address);
            
        } catch (\Exception $e) {
            \Log::error("Erro ao buscar coordenadas para endereço '$address': " . $e->getMessage());
            return null;
        }
    }
}