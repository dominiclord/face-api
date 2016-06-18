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
        $this->search($string);
        return $this;
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
