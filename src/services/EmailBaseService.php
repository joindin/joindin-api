<?php

/**
 * Base Email Class
 *
 * This class provides a base for different email implementations
 *
 * @author Kim Rowan
 */
abstract class EmailBaseService
{
    /**
     * The SwiftMailer object
     */
    protected $mailer;

    /**
     * The SwiftMessage object
     */
    protected $message;

    /**
     * Array of email addresses to send to
     */
    protected $recipients;

    /**
     * Template path, can be changed when testing
     */
    public $templatePath = "../views/emails/";

    /**
     * Make a message to be sent later
     *
     * @param array $config The system config
     * @param array $recipients An array of email addresses
     */
    public function __construct(array $config, array $recipients)
    {
        $transport     = \Swift_MailTransport::newInstance();
        $this->mailer  = \Swift_Mailer::newInstance($transport);
        $this->message = \Swift_Message::newInstance();

        if (isset($config['email']['forward_all_to'])
            && ! empty($config['email']['forward_all_to'])
        ) {
            $this->recipients = array($config['email']['forward_all_to']);
        } else {
            $this->recipients = $recipients;
        }

        $this->message->setFrom($config['email']['from']);
    }

    /**
     * Take the template and the replacements, return markdown
     * with the correct values in it
     *
     * @param string $templateName
     * @param array $replacements
     * @return string
     */
    public function parseEmail($templateName, array $replacements)
    {
        $template = file_get_contents($this->templatePath . $templateName)
                    . file_get_contents($this->templatePath . 'signature.md');

        $message = $template;
        foreach ($replacements as $field => $value) {
            $message = str_replace('[' . $field . ']', $value, $message);
        }

        return $message;
    }

    /**
     * Set the body of the message
     */
    protected function setBody($body)
    {
        $this->message->setBody($body);

        return $this;
    }

    /**
     * Set the HTML body of the message
     *
     * Call setBody first
     */
    protected function setHtmlBody($body)
    {
        $this->message->addPart($body, 'text/html');

        return $this;
    }

    /**
     * Send the email that we created
     */
    protected function dispatchEmail()
    {
        foreach ($this->recipients as $to) {
            $this->message->setTo($to);
            $this->mailer->send($this->message);
        }
    }

    /**
     * Set the subject line of the email
     */
    protected function setSubject($subject)
    {
        $this->message->setSubject($subject);
    }

    /**
     * Set the reply to header
     */
    protected function setReplyTo($email)
    {
        $this->message->setReplyTo($email);
    }

    /**
     * Get recipients list to check it
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * Markdown to HTML
     */
    public function markdownToHtml($markdown)
    {
        $messageHTML = Michelf\Markdown::defaultTransform($markdown);

        return $messageHTML;
    }

    public function htmlToPlainText($html)
    {
        return strip_tags($html);
    }
}
