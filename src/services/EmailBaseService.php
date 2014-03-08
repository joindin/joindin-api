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

    abstract public function parseEmail();

    /**
     * Make a message to be sent later
     *
     * @param array $recipients An array of email addresses
     */
    public function __construct($recipients) {
        $transport = \Swift_MailTransport::newInstance();
        $this->mailer  = \Swift_Mailer::newInstance($transport);
        $this->message = \Swift_Message::newInstance();

        // TODO allow override so on dev all emails go to one address
        $this->recipients = $recipients;
        $this->message->setFrom("info@joind.in");
    }

    /**
     * Set the body of the message
     */
    public function setBody($body) {
        $this->message->setBody($body);
        return $this;
    }

    /**
     * Set the HTML body of the message
     *
     * Call setBody first
     */
    public function setHtmlBody($body) {
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

}
