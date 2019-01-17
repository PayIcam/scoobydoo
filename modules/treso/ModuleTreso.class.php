<?php

require_once 'modules/Module.class.php';

class ModuleTreso extends Module {

	protected $service = "TRESO";

	protected function action_index() {
		// Get fundations
        $fundations = $this->json_client->getFundations();

		$this->view->set_template('html');
		$this->view->set_view($this->get_path_module()."view/index.phtml");
		$this->view->add_param("fundations", $fundations);
		$this->view->add_param("url", $this->get_link_to_action("details"));
	}

	protected function action_details() {
		$fun_id = $_GET['fun_id'];
		if(empty($fun_id)) {
			return $this->super_treso();
		}
		// Get fundation
        $fundations = $this->json_client->getFundations();
		foreach($fundations as $fun) {
			if($fun->fun_id == $_GET['fun_id']) {
				$fundation = $fun;
			}
		}

		$this->view->set_template('html');
        $this->view->set_view($this->get_path_module()."view/treso.phtml");

        $this->view->add_param("fundation", $fundation);
        $this->view->add_param("details", $this->json_client->getDetails(array("fun_id" => $fun_id)));
        $this->view->add_param("url_ask", $this->get_link_to_action("askreversement")."&fun_id=".$fun_id);
        $this->view->add_param("url_journal", $this->get_link_to_action("journal")."&fun_id=".$fun_id);
	}

	protected function action_journal() {
		$fun_id = $_GET['fun_id'];
		if(empty($fun_id)) {
			return $this->index();
		}
		// Get fundation
        $fundations = $this->json_client->getFundations();
		foreach($fundations as $fun) {
			if($fun->fun_id == $_GET['fun_id']) {
				$fundation = $fun;
			}
		}
		$start = isset($_POST['start']) ? $_POST['start'] : (isset($_GET['start']) ? $_GET['start'] : date("Y-m-d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))));
		$end = isset($_POST['end']) ? $_POST['end'] : (isset($_GET['end']) ? $_GET['end'] : date("Y-m-d H:i:s"));

		$this->view->set_template('html');
		$this->view->set_view($this->get_path_module()."view/journal.phtml");
		$this->view->add_param("journal", $this->json_client->getExport(array("fun_id" => $fun_id, "start" => $start, "end" => $end)));
		$this->view->add_param("start", $start);
		$this->view->add_param("end", $end);
		$this->view->add_param("fundation", $fundation);
		$this->view->add_param("url_journal", $this->get_link_to_action("journal")."&fun_id=".$fun_id);

	}

	protected function action_askreversement() {
		$fun_id = $_GET['fun_id'];
		global $CONF;

		require 'vendor/autoload.php';
		$mail = new PHPMailer;

		// $mail->SMTPDebug = 3;                               // Enable verbose debug output

		$mail->isSMTP();                         // Set mailer to use SMTP
		$mail->Host = $CONF['PHPMailer']['Host'];             // Specify main and backup SMTP servers
		$mail->SMTPAuth = $CONF['PHPMailer']['SMTPAuth'];     // Enable SMTP authentication
		$mail->Username = $CONF['PHPMailer']['Username'];     // SMTP username
		$mail->Password = $CONF['PHPMailer']['Password'];     // SMTP password
		$mail->SMTPSecure = $CONF['PHPMailer']['SMTPSecure']; // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $CONF['PHPMailer']['Port'];             // TCP port to connect to
        $mail->Encoding = 'base64';
        $mail->CharSet = 'UTF-8';

		$mail->setFrom('noreply.payicam@gmail.com', 'No Reply PayIcam');

		foreach ($CONF['mails_tresorier'] as $email) {
			$mail->addAddress($email);
		}

		$mail->isHTML(true);                                  // Set email format to HTML

		$subject = 'Demande de reversement fondation #'.$fun_id;
		$mail->Subject = $subject;
		$mail->Body    = '
		<html>
			<head>
				<meta charset="utf-8">
				<title>'.$subject.'</title>
			</head>
			<body>
				<h1>'.$subject.'</h1>
				<p>Tu as une nouvelle demande de reversement sur PayIcam</p>
				<p><a href="'.$CONF['scoobydoo_url'].'?module=treso&action=details&fun_id=" title="pour t\'ammener direct à la bonne page">lien vers la super tresorerie de PayIcam</a></p>
                <p>A bientôt,</p>
				<p><em>PayIcam</em></p>
			</body>
		</html>
		';
		$mail->AltBody = $subject."\n".
			'Tu as une nouvelle demande de reversement sur PayIcam'."\n".
			$CONF['scoobydoo_url'].'?module=treso&action=details&fun_id='."\n".
			'lien vers la super tresorerie de PayIcam'."\n".
            'A bientôt,'."\n".
            "PayIcam";


		$this->json_client->askReversement(array("fun_id" => $fun_id));
		if(!$mail->send()) {
		    die('Message could not be sent.');
		} else {
		    // echo 'Message has been sent';
		}

		header("Location: ".$this->get_link_to_action("details")."&fun_id=".$fun_id);
		exit();
	}

	protected function super_treso() {
		$isAdmin = $this->json_client->isAdmin();
		if(!$isAdmin) {
			header("Location: ".$this->get_link_to_action("index"));
			exit();
		}

		$this->view->set_template('html');
		$this->view->set_view($this->get_path_module()."view/supertreso.phtml");

		$this->view->add_param("details",  $this->json_client->getDetails());
		$this->view->add_param("url_journal", $this->get_link_to_action("journal"));
		$this->view->add_param("url_rev",  $this->get_link_to_action("reversement"));
	}

	protected function action_reversement() {
        require 'vendor/autoload.php';
		$isAdmin = $this->json_client->isAdmin();
		if(!$isAdmin) {
			header("Location: ".$this->get_link_to_action("index"));
			exit();
		}

		if(isset($_POST['rev_id'])) {
			// Do reversement
			$rev_id = $_POST['rev_id'];
			$taux = $_POST['taux'];
			$frais = $_POST['frais'];

			$this->json_client->makeReversement(array(
				"rev_id" => $rev_id,
				"taux" => $taux*100,
				"frais" => $frais*100
			));

            $reversement = $this->json_client->getReversement(array("rev_id" => $rev_id));
            $asked_by_email = $this->json_client->getUserEmail(array("usr_id" => $reversement->usrAsk));

            global $CONF;
            $mail = new PHPMailer;

            // $mail->SMTPDebug = 3;                               // Enable verbose debug output

            $mail->isSMTP();                         // Set mailer to use SMTP
            $mail->Host = $CONF['PHPMailer']['Host'];             // Specify main and backup SMTP servers
            $mail->SMTPAuth = $CONF['PHPMailer']['SMTPAuth'];     // Enable SMTP authentication
            $mail->Username = $CONF['PHPMailer']['Username'];     // SMTP username
            $mail->Password = $CONF['PHPMailer']['Password'];     // SMTP password
            $mail->SMTPSecure = $CONF['PHPMailer']['SMTPSecure']; // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $CONF['PHPMailer']['Port'];             // TCP port to connect to
            $mail->Encoding = 'base64';
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('noreply.payicam@gmail.com', 'No Reply PayIcam');

            $subject = 'Reversement effectué !';
            $mail->Subject = $subject;
            $mail->Body    = '
            <html>
                <head>
                    <meta charset="utf-8">
                    <title>'.$subject.'</title>
                </head>
                <body>
                    <h1>'.$subject.'</h1>
                    <p>Le reversement demandé le ' . $reversement->created .  ' vient d\'être fait !</p>
                    <p>A bientôt,</p>
                    <p><em>PayIcam</em></p>
                </body>
            </html>
            ';
            $mail->AltBody = $subject."\n".
                'Le reversement demandé le ' . $reversement->created .  ' vient d\'être fait !'."\n".
                'A bientôt,'."\n".
                "PayIcam";


            $mail->addAddress($asked_by_email);

            $mail->isHTML(true);                                  // Set email format to HTML

            if(!$mail->send()) {
                die('Message could not be sent.');
            }

			header("Location: ".$this->get_link_to_action("details")."&fun_id=");
			exit();
		}

		$this->view->add_param("reversement", $this->json_client->getReversement(array("rev_id" => $_GET['rev_id'])));
		$this->view->add_param("url_journal", $this->get_link_to_action("journal"));
		$this->view->add_param("fundations", $this->json_client->getFundations());
		$this->view->set_template('html');
		$this->view->set_view($this->get_path_module()."view/reversement.phtml");



	}
}

?>
