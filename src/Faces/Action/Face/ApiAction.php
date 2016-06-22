<?php

namespace Faces\Action\Face;

// PSR-7 (http messaging) dependencies
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// Intra-module (`locomotive`) dependencies
use \Faces\Object\Face;
use \Faces\Object\Tag;
use \Faces\Action\AbstractAction;

class ApiAction extends AbstractAction {

    /**
     * Commands are basically a protected namespace
     */
    protected $commands = [
        'create',
        'list',
        'help'
    ];

    /**
     * In memory storage of face collection
     */
    private $faces;

    /**
     * Text received from slack
     */
    private $text;

    /**
     * Emotion extracted from text
     */
    private $emotion;

    /**
     * User querying the API
     */
    private $userID;
    private $userName;

    /**
     * Content returned to Slack
     */
    private $results = [];

    private $hasResults;

    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getParams();
        $tokens = isset($this->appConfig()->slack_permitted_tokens) ? $this->appConfig()->slack_permitted_tokens : [];

        $this->setMode('json');

        if (!isset($params['token']) || !in_array($params['token'], $tokens) ||
            !isset($params['text']) || empty($params['text'])) {
            $this
                ->setResponseType('ephemeral')
                ->setText('Authentification error.');
            return $response;
        }

        $this
            ->setResponseUrl($params['response_url'])
            ->setUserName($params['user_name'])
            ->setUserId($params['user_id'])
            ->parse($params['text']);

        return $response;
    }

    private function parse($string)
    {

        $firstStringRegex = '/(?<=\>)\b\w*\b|^\w*\b/i';
        preg_match($firstStringRegex, $string, $firstString);
        $command = $firstString[0];

        // If command is not found, we want to trigger an emotion
        if (in_array($command, $this->commands)) {
            switch ($command) {
                case 'list' :
                    $this->listTags($string);
                    break;
                case 'create' :
                    $this->createFace($string);
                    break;
                default:
                    $this->search($string);
            }
        } else {
            $this->search($string);
        }
    }

    private function search($keyword)
    {
        $loader = $this->collection('faces/object/face');
        $faceProto = $this->proto('faces/object/face');
        $tagProto = $this->proto('faces/object/tag');
        $faceTable = $faceProto->source()->table();
        $tagTable = $tagProto->source()->table();
        $keyword = htmlspecialchars_decode($keyword, ENT_QUOTES);

        $this->setEmotion($keyword);

        $keyword = '%'.filter_var($keyword, FILTER_SANITIZE_STRING).'%';

        $q = '
            SELECT ' . $faceTable . '.* FROM ' . $tagTable . '
            LEFT JOIN ' . $faceTable . ' ON FIND_IN_SET(' . $tagTable . '.id , ' . $faceTable . '.tags)
            WHERE 1 = 1
            AND (' . $faceTable . '.active = 1)
            AND (' . $tagTable . '.name LIKE "' . $keyword . '")
            GROUP BY id';

        $faces = $loader->loadFromQuery($q);

        $this
            ->setFaces($faces)
            ->convertFacesToSlackAttachments();

        return $this;
    }

    private function createFace($string) {
        // Ex.:
        // $arguments = 'create [happy, sad, Eric](http://domain.com/image.jpg)';

        // Basic regex
        $tagsRegex = '/\[((.)*?)\]/i';
        $urlRegex = '/\(((.)*?)\)/i';

        // Match all
        preg_match($tagsRegex, $string, $tagArray);
        preg_match($urlRegex, $string, $url);

        if (!empty($tagArray) && !empty($tagArray[1])) {
            // Returns an array of tags.
            $tagArray = $tagArray[1];
            $tagArray = explode(',', $tagArray);
            array_walk($tagArray, function (&$arg){
                $arg = trim($arg);
            });
        }

        // The image if the first match
        $imageUrl = (!empty($url[1])) ? $url[1] : '';

        if (!empty($tagArray) && !empty($imageUrl)) {
            $tagIds = [];
            // Try to load the tags
            foreach ($tagArray as $tagString) {
                // Here we test for existing category by name
                $tagModel = $this
                    ->obj('faces/object/tag')
                    ->loadFrom('name', $tagString);

                if (!$tagModel->id()) {
                    $tagModel = $this
                        ->obj('faces/object/tag')
                        ->setData([
                            'name' => $tagString
                        ]);

                    $tagModel->save();
                }

                $tagIds[] = $tagModel->id();


            }

            // Try to save the face
            $faceModel = $this->obj('faces/object/face');

            // Start by faking a file upload
            $tempName = ini_get('upload_tmp_dir') . '/php' . uniqid();
            var_dump($tempName);
            $tempName = tempnam($faceModel->p('image')->uploadPath(), 'php_files');
            var_dump($tempName);

            $originalName = basename(parse_url($imageUrl, PHP_URL_PATH));
            $imgRawData = file_get_contents($imageUrl);

            // @todo Test for size
            $uploadMaxFilesize = ini_get('upload_max_filesize');

            file_put_contents($tempName, $imgRawData);

            $_FILES['image'] = [
                'name' => $originalName, // @todo Could be changed to custom filename
                'type' => mime_content_type($tempName),
                'tmp_name' => $tempName,
                'error' => 0,
                'size' => strlen($imgRawData)
            ];

            // Create and save the model
            $faceModel->setData([
                'active' => true,
                'filename' => $originalName,
                'tags' => $tagIds
            ]);

            $faceModel->save();

            unlink($tempName);

            die();
        } else {
            $this
                ->setResponseType('ephemeral')
                ->setText('Something went wrong with your command');
        }
    }

    /**
     * Formatted faces.
     * @return [type] [description]
     */
    private function convertFacesToSlackAttachments()
    {
        $faces = $this->faces();
        $attachments = [];

        foreach ($faces as $face) {
            $attachments[] = [
                'fallback' => $this->userName() . ' is feeling ' . $this->emotion(),
                'image_url' => $this->baseUrl() . $face->image()
            ];
        }

        // Pick a random face
        if (count($faces) > 1) {
            $attachments = $attachments[rand(0, count($faces) - 1)];
        }

        $text = (count($faces) > 0) ? $this->userName() . ' is feeling ' . $this->emotion() . '.' : 'Couldn\'t find a face for that emotion. Maybe you should create it yourself. Or go back to work. Thanks.';
        $responseType = (count($faces) > 0) ? 'in_channel' : 'ephemeral';

        $this
            ->setText($text)
            ->setResponseType($responseType)
            ->setAttachments($attachments);

        return $this;
    }

    /**
     * We return an empty 200 response to Slack since we make use of their delayed response concept
     * It allows us to not output the user's slash command in the channel
     * @return array
     */
    public function results()
    {
        $this->setSuccess(true);
        return $this->results;
        // Send an asynchronous request.
        // $client = new \GuzzleHttp\Client();
        // $request = new \GuzzleHttp\Psr7\Request('POST', $this->responseUrl(), ['Content-Type' => 'application/json'], http_build_query($this->results));
        // $promise = $client->sendAsync($request)->then(function ($response) {
        // });
        // $promise->wait();

        // $this->setSuccess(true);
        // return [];
    }

    /* Setters */
    public function setEmotion($emotion)
    {
        $this->emotion = $emotion;
        return $this;
    }
    public function setFaces($faces)
    {
        $this->faces = $faces;
        return $this;
    }
    public function setText($text)
    {
        $this->results['text'] = $text;
        return $this;
    }
    public function setResponseType($responseType)
    {
        $this->results['response_type'] = $responseType;
        return $this;
    }
    public function setAttachments($attachments)
    {
        if (!empty($attachments)) {
            $this->results['attachments'] = $attachments;
        }
        return $this;
    }
    public function setUserName($userName)
    {
        $this->userName = $userName;
        return $this;
    }
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
    public function setResponseUrl($responseUrl)
    {
        $this->responseUrl = $responseUrl;
        return $this;
    }

    /* Getters */
    public function faces() { return $this->faces; }
    public function emotion() { return $this->emotion; }
    public function userName() { return $this->userName; }
    public function userId() { return $this->userId; }
    public function responseUrl() { return $this->responseUrl; }
}
