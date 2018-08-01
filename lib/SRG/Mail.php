<?php
  namespace SRG;

  class Mail {
    public static function notify_unilateral_termination($repository) {
      $message = [
        "Dear repository administrator,\r\n\r\n",
        "the OAI PMH Static Repository Gateway at\r\n",
        getenv('SRG_BASE_URL'), "\r\n\r\n",
        "has terminated mediation for your repository. Please contact\r\n",
        getenv('SRG_ADMIN_EMAIL'), "\r\n\r\n",
        "if you have any questions\r\n\r\n",
        "Kind regards,\r\n",
        getenv('SRG_ORGANIZATION')
      ];

      $headers = [
        "From: ", getenv('SRG_ADMIN_EMAIL'), "\r\n",
        "Reply-To: ", getenv('SRG_ADMIN_EMAIL'),
      ];

      $accepted = mail(
        $repository->admin_email,
        "mediation terminated for {$repository->url}",
        join('', $message),
        join('', $headers)
      );

      if ($accepted) {
        \SRG::log("mail notification sent to {$repository->admin_email}.");
      } else {
        \SRG::log("mail notification to {$repository->admin_email} could not be sent. Please check your mail logs");
      }
    }
  }
?>