<?php

namespace App\Conference;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Log;
class ConferenceService {

    protected $sid;
    protected $token;
    protected $key;
    protected $secret;

    public function __construct()
    {
       $this->sid = config('services.twilio.sid');
       $this->token = config('services.twilio.token');
       $this->key = config('services.twilio.key');
       $this->secret = config('services.twilio.secret');
    }

	public function storeData($request, $slug) {
        $message = '';
        $roomId = 0;
        try {
            $client = new Client($this->sid, $this->token);
            $exists = $client->video->rooms->read([ 'uniqueName' => $slug]);
            if (empty($exists)) {
               $room = $client->video->rooms->create([
                   'uniqueName' => $slug,
                   'type' => 'group',
                   'recordParticipantsOnConnect' => true
               ]);
               $roomId = $room->sid;
               $status = true;
            }

        } catch (Exception $e) {
            $status = true;
            $message = $e->getMessage();
        }

        return array('status' => $status, 'message' => $message, 'room_id' => $roomId);
	}

    public function showDetails($roomName='', $identity='')
    {
       $token = new AccessToken($this->sid, $this->key, $this->secret, 3600, $identity);

       $videoGrant = new VideoGrant();
       $videoGrant->setRoom($roomName);

       $token->addGrant($videoGrant);

       return array('accessToken' => $token->toJWT());
    }

    public function closeConnection($conference, $id)
    {   
        $message = '';
        $compositionId = 0;
        try {
            $client = new Client($this->sid, $this->token);
            $composition = $client->video->compositions->create($conference->room_id, [
                'audioSources' => '*',
                'videoLayout' =>  array(
                                    'grid' => array (
                                      'video_sources' => array('*')
                                    )
                                  ),
                'statusCallback' => url('video-conference/call-back'),
                'statusCallbackMethod' => "GET",
                'format' => 'mp4'
            ]);
            $compositionId = $composition->sid;
            $status = true;
        } catch (\Twilio\Exceptions\RestException $e) {
            $status = false;
            $message = $e->getMessage();
        }

         return array('status' => $status, 'message' => $message, 'compose_id' => $compositionId);
    }

    public function saveVideo($school_slug,$cid='')
    {
        try {
        $client = new Client($this->sid, $this->token);
        $uri = "https://video.twilio.com/v1/Compositions/".$cid."/Media?Ttl=3600";
        $response = $client->request("GET", $uri);
        $mediaLocation = $response->getContent()["redirect_to"];
        $file_path=$school_slug.'/uploads/live-stream/stream_'.$cid.'.mp4';
        $path=\Storage::put($file_path, fopen($mediaLocation, 'r'));
        //@file_put_contents($school_slug.'/uploads/'.$cid.'.mp4', fopen($mediaLocation, 'r'));
        return $file_path;

        } catch (Exception $e) {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }

    }

    public function showDuration($roomName='')
    {
       $client = new Client($this->sid, $this->token);
       //$participant = $client->video->rooms($roomName)->fetch();
       $uri = "https://video.twilio.com/v1/Rooms/".$roomName."/Participants";
       
        $response = $client->request("GET", $uri);
        $list=$response->getContent();
      return $list;
    }
}