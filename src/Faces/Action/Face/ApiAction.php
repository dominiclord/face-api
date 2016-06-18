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
	 * Message returned to Slack
	 */
	private $text;

    private $hasResults;

	/**
	 * @param RequestInterface  $request  A PSR-7 compatible Request instance.
	 * @param ResponseInterface $response A PSR-7 compatible Response instance.
	 * @return ResponseInterface
	 */
	public function run(RequestInterface $request, ResponseInterface $response)
	{
		$params = $request->getParams();
        $tokens = isset($this->appConfig()->slack_tokens) ? $this->appConfig()->slack_tokens : [];

        $this->setMode('json');

        if (!isset($params['token']) || !in_array($params['token'], $tokens) ||
           !isset($params['text']) || empty($params['text'])) {
            $this->setSuccess(false);
            $this->setText('Authentification error.');
            return $response;
        }

        $this
            ->parse($params['text'])
            ->setSuccess(true);

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
        $keyword = '%'.filter_var($keyword, FILTER_SANITIZE_STRING).'%';

        $q = '
            SELECT ' . $faceTable . '.* FROM ' . $tagTable . '
            LEFT JOIN ' . $faceTable . ' ON FIND_IN_SET(' . $tagTable . '.id , ' . $faceTable . '.tags)
            WHERE 1 = 1
            AND (' . $faceTable . '.active = 1)
            AND (' . $tagTable . '.name LIKE "' . $keyword . '")
            GROUP BY id';

        $this->logger->debug($q);

        $faces = $loader->loadFromQuery($q);
        $this->setFaces($faces);

        return $this;
	}

	/**
	 * Formatted faces.
	 * @return [type] [description]
	 */
	private function facesAsSlackAttachments()
	{
		$faces = $this->faces();

		if (!$faces) {
			return [];
		}

		$out = [];
		foreach ($faces as $face) {
			$out[] = [
                'fallback' => 'This is a face.',
				'image_url' => $this->baseUrl() . $face->image()
			];
		}

        $text = (count($out) > 0) ? 'Your face.' : 'Couldn\'t find a face for that emotion. Maybe you should create it yourself. Or go back to work. Thanks.';
        $this->setText($text);

		return $out;
	}

	/**
	* @return array
	*/
	public function results()
	{
        $ret = [];
        $responseType = 'ephemeral';

        if ($this->success()) {
            $attachments = $this->facesAsSlackAttachments();

            if (count($attachments) > 0) {
                $ret['attachments'] = $attachments;
                $responseType = 'in_channel';
            }
        }

        $ret['text'] = $this->text();
        $ret['response_type'] = $responseType;

		return $ret;
	}

    /* Setters */
    public function setFaces($faces)
    {
        $this->faces = $faces;
        return $this;
    }
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /* Getters */
    public function faces() { return $this->faces; }
    public function text() { return $this->text; }

}
