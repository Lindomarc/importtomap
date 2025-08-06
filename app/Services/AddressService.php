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
        // Busca na tabela local primeiro
        $state = $address['state'] ?? 'PR';
        // Log::info('address', $address);
        $Address = null;
        if(!!$address['cep'] && !!$address['number']){
            $Address = Address::where('cep', $address['cep'])
            ->where('number', $address['number'])
            ->first();

        }
        if ($Address) {
            return $Address;
        }

        if(!!$address['cep']){
            $Address = Address::where('cep', $address['cep'])
            ->first();
        }

        if ($Address) {
            return $Address;
        }

        if(!!$address['street'] && !!$address['number'] && !!$address['city'])
        $Address = Address::where('street', $address['street'])
        ->where('number', $address['number'])
        ->where('city', $address['city'])
        ->first();

        if ($Address) {
            return $Address;
        }

        if(!!$address['street'] && $address['city'])
        $Address = Address::where('street', $address['street'])
        ->where('city', $address['city'])
        ->first();

        if ($Address) {
            return $Address;
        }

        if(!!$address['city'] && !$address['street'])

        $Address = Address::where('city', $address['city'])
        ->where('state', $state)
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
        if($address['city'] && $state ){
            $search .= $address['city'] . ', '. $state.', ';
        } 

        if($search){
            
            $search .= ", Brasil";

            $coordinates = $this->fetchCoordinatesFromAddress($search);
            
            if ($coordinates) {   
                $data = [
                    'city' => $address['city'],
                    'cep' => $address['cep'],
                    'number' => $address['number'],
                    'street' => $address['street'],
                    'state' => $state,
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng']
                ];
                // Log::info('$data',[$address, $data]);
                return Address::create($data);
            }
        }
        
        return null;
    }
    
   
    private function fetchCoordinatesFromAddress($search): ?array
    {
        // Log::info("fetchCoordinatesFromAddress",[$search]);
        try {
            // Monta o endereço completo
             
            // Exemplo usando OpenCage Geocoding API
            $apiKey = env('OPENCAGE_API_KEY');
            $query = urlencode($search);
            
            $url = "https://api.opencagedata.com/geocode/v1/json?q={$query}&key={$apiKey}&limit=1&countrycode=br";
            // \Log::info('import', [$url]);

            $response = Http::timeout(10)->get($url);
            // Log::info("response", [$response]);

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
        }
        return [];

    }
}