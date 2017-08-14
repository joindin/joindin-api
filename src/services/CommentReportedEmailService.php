<?php

class CommentReportedEmailService extends EmailBaseService
{
    /** @var array */
    protected $comment;

    /** @var array */
    protected $event;

    /** @var string */
    protected $website_url;

    public function __construct(array $config, array $recipients, array $comment, array $event)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs comment info
        $this->comment = $comment['comments'][0];
        $this->website_url = $config['website_url'];
        $this->event = $event['events'][0];

    }

    public function sendEmail()
    {
        $this->setSubject("Joind.in: A comment was reported");

        $byLine = '';

        if (isset($this->comment['user_display_name'])) {
            $byLine = ' by ' . $this->comment['user_display_name'];
        }

        if (empty($byLine) && isset($this->comment['username'])) {
            $byLine = ' by' . $this->comment['username'];
        }

        if (empty($byLine)) {
            $byLine = ' by (anonymous)';
        }

        $replacements = array(
            "eventName" => $this->event['name'],
            "title"     => $this->comment['talk_title'],
            "rating"    => $this->comment['rating'],
            "comment"   => $this->comment['comment'],
            "byline"    => $byLine,
            "link"      => $this->linkToReportedCommentsForEvent()
        );

        $messageBody = $this->parseEmail("commentReported.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }

    /**
     * @return string
     */
    private function linkToReportedCommentsForEvent()
    {
        /*
         * As far as I can tell, this Service is only used in one place, but just in case,
         * let's allow backward compatibility with the old class signature
         */
        if (!$this->event) {
            return '';
        }

        return '[' . $this->website_url
            . '/event/' . $this->event['url_friendly_name']
            . '/reported-comments' . '](' . $this->website_url
            . '/event/' . $this->event['url_friendly_name']
            . '/reported-comments' . ')';
    }
}
