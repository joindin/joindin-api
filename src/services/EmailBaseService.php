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

    abstract protected function parseEmail();

    /**
     * Make a message to be sent later
     *
     * @param array $recipients An array of email addresses
     */
    public function __construct($config, $recipients) {
        $transport = \Swift_MailTransport::newInstance();
        $this->mailer  = \Swift_Mailer::newInstance($transport);
        $this->message = \Swift_Message::newInstance();

        if(isset($config['email']['forward_all_to']) 
            && !empty($config['email']['forward_all_to'])) {
                $this->recipients = array($config['email']['forward_all_to']);
        } else {
            $this->recipients = $recipients;
        }

        $this->message->setFrom($config['email']['from']);
    }

    /**
     * Set the body of the message
     */
    protected function setBody($body) {
        $this->message->setBody($body);
        return $this;
    }

    /**
     * Set the HTML body of the message
     *
     * Call setBody first
     */
    protected function setHtmlBody($body) {
        $this->message->addPart($body, 'text/html');
        return $this;
    }

    /**
     * Send the email that we created
     */
    protected function dispatchEmail()
    {
        foreach($this->recipients as $to) {
            $this->message->setTo($to);
            $this->mailer->send($this->message);
        }
    }

    /**
     * Set the subject line of the email
     */
    protected function setSubject($subject) {
        $this->message->setSubject($subject);
    }

}
