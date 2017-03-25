<?php

namespace GDE;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * Oferecimento
 *
 * @ORM\Table(
 *   name="gde_oferecimentos",
 *   indexes={@ORM\Index(name="turma", columns={"turma"})})
 * @ORM\Entity
 */
class Oferecimento extends Base {
	/**
	 * @var integer
	 *
	 * @ORM\Column(type="integer", options={"unsigned"=true}, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	protected $id_oferecimento;

	/**
	 * @var Disciplina
	 *
	 * @ORM\ManyToOne(targetEntity="Disciplina", inversedBy="oferecimentos")
	 * @ORM\JoinColumn(name="sigla", referencedColumnName="sigla")
	 */
	protected $disciplina;

	/**
	 * @var Periodo
	 *
	 * @ORM\ManyToOne(targetEntity="Periodo")
	 * @ORM\JoinColumn(name="id_periodo", referencedColumnName="id_periodo")
	 */
	protected $periodo;

	/**
	 * @var Professor
	 *
	 * @ORM\ManyToOne(targetEntity="Professor", inversedBy="oferecimentos")
	 * @ORM\JoinColumn(name="id_professor", referencedColumnName="id_professor")
	 */
	protected $professor;

	/**
	 * @var OferecimentoReserva
	 *
	 * @ORM\OneToMany(targetEntity="OferecimentoReserva", mappedBy="oferecimento")
	 */
	protected $reservas;

	/**
	 * @var Aluno
	 *
	 * @ORM\ManyToMany(targetEntity="Aluno", mappedBy="oferecimentos")
	 */
	protected $alunos;

	/**
	 * @var Aluno
	 *
	 * @ORM\ManyToMany(targetEntity="Aluno", mappedBy="trancadas")
	 */
	protected $alunos_trancadas;

	/**
	 * @var Dimensao
	 *
	 * @ORM\ManyToMany(targetEntity="Dimensao", inversedBy="oferecimentos")
	 * @ORM\JoinTable(name="gde_r_oferecimentos_dimensoes",
	 *      joinColumns={@ORM\JoinColumn(name="id_oferecimento", referencedColumnName="id_oferecimento")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="id_dimensao", referencedColumnName="id_dimensao")}
	 * )
	 */
	protected $dimensoes;

	/**
	 * @var string
	 *
	 * @ORM\Column(type="string", length=2, nullable=false)
	 */
	protected $turma;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="smallint", options={"default"=0}, nullable=false)
	 */
	protected $vagas = 0;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(type="boolean", options={"default"=0}, nullable=false)
	 */
	protected $fechada = false;

	/**
	 * @var string
	 *
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	protected $pagina;

	// ToDo: Remover isto!
	static $ordens_nome = array('Relev&acirc;ncia', 'Sigla e Turma', 'Nome', 'Professor', 'Per&iacute;odo');
	static $ordens_inte = array('rank', 'DI.sigla', 'DI.nome', 'P.nome', 'O.periodo');

	/**
	 * Consultar
	 *
	 * Efetua uma consulta por Oferecimentos
	 *
	 * @param $param
	 * @param null $ordem
	 * @param null $total
	 * @param int $limit
	 * @param int $start
	 * @return Oferecimento[]
	 */
	public static function Consultar($param, $ordem = null, &$total = null, $limit = -1, $start = -1) {
		$qrs = $jns = array();
		if($ordem == null)
			$ordem = "O.id_oferecimento ASC";
		if(($ordem == "DI.sigla ASC") || ($ordem == "DI.sigla DESC"))
			$ordem = ($ordem != "DI.sigla DESC") ? "DI.sigla ASC, O.turma ASC" : "DI.sigla DESC, O.turma DESC";
		if(!empty($param['sigla']))
			if(strlen($param['sigla']) == 5) {
				$qrs[] = "DI.sigla = :sigla";
			} else {
				$qrs[] = "O.sigla LIKE :sigla";
				$param['sigla'] = '%'.$param['sigla'].'%';
			}
		if(!empty($param['periodo'])) {
			$qrs[] = "O.periodo = :periodo";
		}
		if((!empty($param['sigla'])) || (!empty($param['nome'])) || (!empty($param['creditos'])) || (!empty($param['instituto'])) || (!empty($param['nivel'])) || ($ordem == "DI.nome ASC") || ($ordem == "DI.nome DESC"))
			$jns[] = " JOIN O.disciplina AS DI";
		if(!empty($param['nome'])) {
			$qrs[] = "DI.nome LIKE :nome";
			$param['nome'] = '%'.$param['nome'].'%';
		}
		if(!empty($param['creditos']))
			$qrs[] = "DI.creditos = :creditos";
		if(!empty($param['instituto']))
			$qrs[] = "DI.instituto = :instituto";
		if(!empty($param['nivel']))
			$qrs[] = "DI.nivel = :nivel";
		if(!empty($param['turma']))
			$qrs[] = "O.turma = :turma";
		if(!empty($param['professor']))
			$qrs[] = "P.nome LIKE :professor";
		if(!empty($param['professor']) || ($ordem == "P.nome ASC") || ($ordem == "P.nome DESC"))
			$jns[] = " JOIN O.professor AS P";
		if((!empty($param['dia'])) || (!empty($param['horario'])) || (!empty($param['sala'])))
			$jns[] = " JOIN O.dimensoes AS D";
		if(!empty($param['dia']))
			$qrs[] = "D.dia = :dia";
		if(!empty($param['horario']))
			$qrs[] = "D.horario = :horario";
		if(!empty($param['sala']))
			$qrs[] = "D.sala LIKE :sala";
		$where = (count($qrs) > 0) ? implode(" AND ", $qrs) : "TRUE";
		$joins = (count($jns) > 0) ? implode(" ", $jns) : null;
		if($total !== null) {
			$dqlt = "SELECT COUNT(DISTINCT O.id_oferecimento) FROM ".get_class()." AS O ".$joins." WHERE ".$where;
			$total = self::_EM()->createQuery($dqlt)->setParameters($param)->getSingleScalarResult();
		}
		$dql = "SELECT DISTINCT O FROM ".get_class()." AS O ".$joins." WHERE ".$where." ORDER BY ".$ordem;
		$query = self::_EM()->createQuery($dql)->setParameters($param);
		if($limit > 0)
			$query->setMaxResults($limit);
		if($start > -1)
			$query->setFirstResult($start);
		return $query->getResult();
	}

	/**
	 * @param $q
	 * @param null $ordem
	 * @param null $total
	 * @param int $limit
	 * @param int $start
	 * @return Disciplina[]
	 */
	public static function Consultar_Simples($q, $ordem = null, &$total = null, $limit = -1, $start = -1) {
		// ToDo: Pegar nome da tabela das annotations
		if((preg_match('/^[a-z ]{2}\d{3}$/i', $q) > 0) || (mb_strlen($q) < CONFIG_FT_MIN_LENGTH)) {
			if($ordem == null || $ordem == 'rank ASC' || $ordem == 'rank DESC') {
				$extra_join = "";
				$ordem = ($ordem != 'rank DESC')
					? 'O.`id_periodo` ASC, O.`sigla` DESC, O.`turma` DESC'
					: 'O.`id_periodo` DESC, O.`sigla` ASC, O.`turma` ASC';
			} elseif($ordem == "DI.`nome` ASC" || $ordem == "DI.`nome` DESC")
				$extra_join = " JOIN `gde_disciplinas` AS DI ON (O.`sigla` = DI.`sigla`) ";
			elseif($ordem == "P.nome ASC" || $ordem == "P.nome DESC")
				$extra_join = " JOIN `gde_professores` AS P ON (O.`id_professor` = P.`id_professor`) ";
			elseif(($ordem == "O.sigla ASC") || ($ordem == "O.sigla DESC")) {
				$ordem = ($ordem != "O.`sigla` DESC")
					? "O.`sigla` ASC, O.`turma` ASC"
					: "O.`sigla` DESC, O.`turma` DESC";
				$extra_join = "";
			} else
				$extra_join = "";
			if($total !== null)
				$sqlt = "SELECT COUNT(*) AS `total` FROM `gde_oferecimentos` AS O".$extra_join." WHERE O.`sigla` LIKE :q";
			$sql = "SELECT O.* FROM `gde_oferecimentos` AS O".$extra_join." WHERE O.`sigla` LIKE :q ORDER BY ".$ordem." LIMIT ".$start.",".$limit;
			$q = '%'.$q.'%';
		} else {
			//$q = preg_replace('/(\w+)/', '+$1*', $q);
			$q = preg_replace('/(\w{'.CONFIG_FT_MIN_LENGTH.',})/', '+$1*', $q);
			if($ordem == null || $ordem == 'rank ASC' || $ordem == 'rank DESC') {
				$ordem = ($ordem != 'rank DESC')
					? "`rank` ASC, O.id_periodo ASC, O.`sigla` DESC, O.`turma` DESC"
					: "`rank` DESC, O.`id_periodo` DESC, O.`sigla` ASC, O.`turma` ASC";
				$extra_select1 = ", MATCH(P.`nome`) AGAINST(:q) AS `rank`";
				$extra_select2 = ", MATCH(DI.`sigla`, DI.`nome`, DI.`ementa`) AGAINST(:q) AS `rank`";
				$extra_join1 = $extra_join2 = "";
			} elseif($ordem == "DI.nome ASC" || $ordem == "DI.nome DESC") {
				$extra_select1 = $extra_select2 = ", DI.`nome` AS `disciplina`";
				$extra_join1 = "JOIN `gde_disciplinas` AS DI ON (O.`sigla` = DI.`sigla`) ";
				$extra_join2 = "";
				$ordem = ($ordem != "DI.nome DESC") ? "O.`disciplina` ASC" : "O.`disciplina` DESC";
			} elseif($ordem == "P.nome ASC" || $ordem == "P.nome DESC") {
				$extra_select1 = $extra_select2 = ", P.`nome` AS `professor`";
				$extra_join1 = "";
				$extra_join2 = "JOIN `gde_professores` AS P ON (O.`id_professor` = P.`id_professor`) ";
				$ordem = ($ordem != "P.nome DESC")
					? "O.`professor` ASC" : "O.`professor` DESC";
			} elseif(($ordem == "O.sigla ASC") || ($ordem == "O.sigla DESC")) {
				$ordem = ($ordem != "O.`sigla` DESC")
					? "O.`sigla` ASC, O.`turma` ASC"
					: "O.`sigla` DESC, O.`turma` DESC";
				$extra_select1 = $extra_select2 = $extra_join1 = $extra_join2 = "";
			} else
				$extra_select1 = $extra_select2 = $extra_join1 = $extra_join2 = "";
			if($total !== null)
				$sqlt = "SELECT A.`total` + B.`total` AS `total` FROM (SELECT COUNT(*) AS total FROM `gde_oferecimentos` AS O INNER JOIN `gde_professores` AS P ON (P.`id_professor` = O.`id_professor`) WHERE MATCH(P.`nome`) AGAINST(:q IN BOOLEAN MODE)) AS A, (SELECT COUNT(*) AS `total` FROM `gde_oferecimentos` AS O INNER JOIN `gde_disciplinas` AS DI ON (DI.`sigla` = O.`sigla`) WHERE MATCH(DI.`sigla`, DI.`nome`, DI.`ementa`) AGAINST(:q IN BOOLEAN MODE)) AS B";
			$sql = "SELECT O.* FROM ((SELECT O.*".$extra_select1." FROM `gde_oferecimentos` AS O ".$extra_join1."INNER JOIN `gde_professores` AS P ON (P.`id_professor` = O.`id_professor`) WHERE MATCH(P.`nome`) AGAINST(:q IN BOOLEAN MODE) ORDER BY `rank` DESC, O.`id_periodo` DESC) UNION ALL (SELECT O.*".$extra_select2." FROM `gde_oferecimentos` AS O ".$extra_join2."INNER JOIN `gde_disciplinas` AS DI ON (DI.`sigla` = O.`sigla`) WHERE MATCH(DI.`sigla`, DI.`nome`, DI.`ementa`) AGAINST(:q IN BOOLEAN MODE) ORDER BY `rank` DESC, O.`id_periodo` DESC)) AS O ORDER BY ".$ordem." LIMIT ".$start.",".$limit;
		}

		if($total !== null) {
			$rsmt = new ResultSetMappingBuilder(self::_EM());
			$rsmt->addScalarResult('total', 'total');
			$queryt = self::_EM()->createNativeQuery($sqlt, $rsmt);
			$queryt->setParameter('q', $q);
			$total = $queryt->getSingleScalarResult();
		}

		$rsm = new ResultSetMappingBuilder(self::_EM());
		$rsm->addRootEntityFromClassMetadata(get_class(), 'O');
		$query = self::_EM()->createNativeQuery($sql, $rsm);
		$query->setParameter('q', $q);
		return $query->getResult();
	}

	/**
	 * getSigla
	 *
	 * Retorna a sigla da Disciplina deste Oferecimento
	 *
	 * @param bool $html
	 * @return null
	 */
	public function getSigla($html = false) {
		if($this->getDisciplina(false) === null)
			return null;
		return $this->getDisciplina()->getSigla($html);
	}

	/**
	 * getReservas
	 *
	 * Retorna a lista de Reservas deste Oferecimento, opcionalmente formatadas (HTML)
	 *
	 * @param bool $formatado
	 * @return OferecimentoReserva[]|string
	 */
	public function getReservas($formatado = false) {
		$Reservas = parent::getReservas();
		if($formatado === false)
			return $Reservas;
		else {
			if($Reservas->count() == 0)
				return 'N&atilde;o Dispon&iacute;vel';
			if($Reservas->first()->getCurso(false) === null)
				return 'Sem Reservas';
			else {
				$lista = array();
				foreach($Reservas as $Reserva)
					$lista[] = $Reserva->getCurso(true)->getNome(true)." (".$Reserva->getCurso(true)->getNumero(true).")".(($Reserva->getCatalogo(false) != null) ? " / ".$Reserva->getCatalogo(true) : null);
				return implode("<br />", $lista);
			}
		}
	}

	/**
	 * Monta_Horario
	 *
	 * Organiza as Dimensoes deste Oferecimento
	 *
	 * @return array
	 */
	public function Monta_Horario() {
		$Lista = array();
		foreach($this->getDimensoes() as $Dimensao)
			$Lista[$Dimensao->getDia()][$Dimensao->getHorario()] = $Dimensao->getSala(true)->getNome(true);
		return $Lista;
	}

	/**
	 * Lista_Horario
	 *
	 * Retorna uma lista das Dimensoes deste Oferecimento
	 *
	 * @param bool $cru
	 * @return array
	 */
	public function Lista_Horarios($cru = false) {
		$Lista = array();
		foreach($this->getDimensoes() as $Dimensao)
			$Lista[] = ($cru) ? $Dimensao->getDia().sprintf("%02d", $Dimensao->getHorario()) : array($Dimensao->getDia(), $Dimensao->getHorario(), $Dimensao->getSala(true)->getNome(true));
		return $Lista;
	}

	/**
	 * Formata_Horario
	 *
	 * Retorna um horario formatado para este Oferecimento
	 *
	 * @param $Horario
	 * @param $dia
	 * @param $horario
	 * @return string
	 */
	public static function Formata_Horario($Horario, $dia, $horario) {
		return (isset($Horario[$dia][$horario])) ? (($Horario[$dia][$horario] != '????') ? "<a href=\"".CONFIG_URL."sala/".$Horario[$dia][$horario]."/\">".$Horario[$dia][$horario]."</a>" : $Horario[$dia][$horario]) : "-";
	}

	/**
	 * Viola_Reserva
	 *
	 * Determina se $Usuario cursar este Oferecimento violaria alguma reserva
	 *
	 * @param Usuario $Usuario
	 * @return bool
	 */
	public function Viola_Reserva(Usuario $Usuario) {
		if(count($this->getReservas()) == 0)
			return false;
		foreach($this->getReservas() as $Reserva) {
			if(
				($Reserva->getCurso(false) === null) ||
				(
					($Reserva->getCurso(true)->getID() == $Usuario->getCurso(true)->getID()) &&
					(($Reserva->getCatalogo(false) == null) || ($Reserva->getCatalogo(false) == $Usuario->getCatalogo(false)))
				)
			) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Matriculados
	 *
	 * Retorna o numero de Alunos matriculados neste Oferecimento
	 *
	 * @return integer
	 */
	public function Matriculados() {
		return $this->getAlunos()->count();
	}

	/**
	 * Desistencias
	 *
	 * Retorna o numero de Alunos que trancaram este Oferecimento
	 *
	 * @return integer
	 */
	public function Desistencias() {
		return $this->getAlunos_Trancadas()->count();
	}

	/**
	 * @param bool $agrupar
	 * @return array
	 */
	public function Eventos($agrupar = false) {
		$Lista = array();
		$fh = $lh = null;
		$Horarios = $this->Monta_Horario();
		ksort($Horarios);
		foreach($Horarios as $dia => $Resto) {
			ksort($Resto);
			$horarios = array_keys($Resto);
			sort($horarios);
			$ch = count($horarios);
			$fh = $lh = $horarios[0];
			$sala = $salan = strtoupper($Resto[$horarios[0]]);
			$j = 1;
			for($i = 1; $i < $ch; $i++) {
				if(($horarios[$i] == $fh + $j) && (($agrupar === true) || (strtoupper($Resto[$horarios[$i]]) == $sala))) {
					if(strtoupper($Resto[$horarios[$i]]) != $sala) {
						$sala = strtoupper($Resto[$horarios[$i]]);
						$salan .= '/'.$sala;
					}
					$lh = $horarios[$i];
					$j++;
				} else {
					$Lista[] = array('id' => $this->getID(), 'title' => $this->getSigla().' '.$this->getTurma().' '.$salan, 'start' => '2003-12-0'.($dia-1).'T'.sprintf("%02d", $fh).':00:00-03:00', 'end' => '2003-12-0'.($dia-1).'T'.sprintf("%02d", ($lh+1)).':00:00-03:00');
					$fh = $lh = $horarios[$i];
					$j = 1;
					$sala = strtoupper($Resto[$horarios[$i]]);
				}
			}
			$Lista[] = array('id' => $this->getID(), 'title' => $this->getSigla().' '.$this->getTurma().' '.$salan, 'start' => '2003-12-0'.($dia-1).'T'.sprintf("%02d", $fh).':00:00-03:00', 'end' => '2003-12-0'.($dia-1).'T'.sprintf("%02d", ($lh+1)).':00:00-03:00');
		}
		return $Lista;
	}

}
