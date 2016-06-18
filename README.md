face-api
====================

Query the API with an emotion and a face you shall receive

## Features to implement

- Limit to user IDs
- Look into not outputting `/face [emotion]` into Slack
- Output face using filename property
- Make filename property editable
- Return a random face from search term instead of the same one
- Add message to face
    + ex.: `/face emotion [Your message here]`
- Help function
    + ex.: `/face help`
- List function
    + ex.: `/face list`
    + ex.: `/face list [emotion]`
- Submit a new image from slack
    + ex.: `/face create [retard, ta mere, bene](http://lel.jpg)`
        ```
        /**
         - Thats how array_walk works.
         - @param  string &$arg Argument
         - @return string       Enhanced argument.
         */
        function trimArrayMember(&$arg)
        {
            $arg = trim($arg);
        }

        // Actual command
        $arguments = 'create [retard, ta mere, bene](http://lel.jpg)';

        // Basic regex
        $tags_regex = '/\[((.)*?)\]/i';
        $url_regex = '/\(((.)*?)\)/i';

        // Match all
        preg_match_all($tags_regex, $arguments, $tags);
        preg_match_all($url_regex, $arguments, $url);

        // Maybe if count($tags[1]) > 1 -> erreur?
        // Returns and array of tags.
        $tags = $tags[1][0];
        $tags = explode(',', $tags);
        array_walk($tags, 'trimArrayMember');

        // The image if the first match
        $image = $url[1][0];

        var_dump($tags);
        var_dump($image);
        ```
