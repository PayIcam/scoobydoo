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
		$start = isset($_POST['start']) ? $_POST['start'] : date("Y-m-d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$end = isset($_POST['end']) ? $_POST['end'] : date("Y-m-d H:i:s");

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

		// Plusieurs destinataires
	    $to  = implode(', ', $CONF['mails_tresorier']);
		// Sujet
		$subject = 'Demande de reversement fondation #'.$fun_id;

		//messsage via mail pour demande de reversement
		$message = '
		<html>
			<head>
				<title>'.$subject.'</title>
			</head>
			<body>
				<h1>'.$subject.'</h1>
				<p>Tu as une nouvelle demande de reversement sur Payicam</p>
				<p><a href="'.$CONF['scoobydoo_url'].'?module=treso&action=details&fun_id=" title="pour t\'ammener direct à la bonne page">lien vers la super trésorerie de PayIcam</a></p>
				<p><em>On t\'embrasse, la dev team de PayIcam</em></p>
			</body>
		</html>
		';

	    // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
	    $headers  = 'MIME-Version: 1.0' . "\r\n";
	    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

	    // En-têtes additionnels
	    $headers .= 'From: PayIcam <payicam.lille@gmail.com>' . "\r\n";
	    $headers .= 'Reply-To: PayIcam <payicam.lille@gmail.com>' . "\r\n";
	    $headers .= 'Subject: '.$subject . "\r\n";

		$this->json_client->askReversement(array("fun_id" => $fun_id));

		mail($to, $subject, $message, $headers);

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
		$this->view->add_param("url_rev",  $this->get_link_to_action("reversement"));
	}

	protected function action_reversement() {
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

			header("Location: ".$this->get_link_to_action("details")."&fun_id=");
			exit();
		}

		$this->view->add_param("reversement", $this->json_client->getReversement(array("rev_id" => $_GET['rev_id'])));
		$this->view->add_param("fundations", $this->json_client->getFundations());
		$this->view->set_template('html');
		$this->view->set_view($this->get_path_module()."view/reversement.phtml");



	}
}

?>
