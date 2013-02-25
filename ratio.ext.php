<?php



class EXT_Ratio extends SYS_Model_Database
{
	//--------------------------------------------------------------------------

	public $table = 'ratio';
	public $rules = array();
	public $uid   = 0;

	//--------------------------------------------------------------------------

	public function init()
	{
		$this->uid = $this->user->id;

		sys::set_config_items(&$this, 'ratio');

		$this->fields['ratio'] = array(
			'uid' => array(),
			'rule' => array(),
			'score' => array(),
			'postdate' => array(
				'default' => time()
			),
		);
	}

	//--------------------------------------------------------------------------

	public function prepare_row_result(&$row)
	{
		$row->title = $this->rules[$row->rule]['title'];

		$row->score = ($row->score > 0 ? '+' : '') . $row->score;

		if ($row->postdate) $row->postdate = date('d.m.Y - H:i', $row->postdate);

		return parent::prepare_row_result(&$row);
	}

	//--------------------------------------------------------------------------

	public function up($rule)
	{
		$price = $this->price($rule);
		if ( ! $price) return;

		$this->insert($this->table(), array(
			'uid'   => $this->uid,
			'rule'  => $rule,
			'score' => $price
		));

		if ($this->uid == $this->user->id)
		{
			$score = $this->user->score;
		}
		else
		{
			$score = $this->db->select('score')->where('users.id=?', $this->uid)->get('users')->row()->score;
		}

		$this->db->where('id=?', $this->uid);
		$this->db->update('users', array(
			'score' => $score + $price
		));
	}

	//--------------------------------------------------------------------------

	public function down($rule)
	{
		$price = $this->price($rule);
		if ( ! $price) return;

		$this->db->where('uid=? AND rule=?', $this->uid, $rule)->limit(1);
		$this->delete();

		if ($this->uid == $this->user->id)
		{
			$score = $this->user->score;
		}
		else
		{
			$score = $this->db->select('score')->where('users.id=?', $this->uid)->get('users')->row()->score;
		}

		$this->db->where('id=?', $this->uid);
		$this->db->update('users', array(
			'score' => $score - $price
		));
	}

	//--------------------------------------------------------------------------

	public function price($rule)
	{
		return isset($this->rules[$rule]) ? $this->rules[$rule]['score'] : 0;
	}

	//--------------------------------------------------------------------------

	public function get()
	{
		$this->db->where('uid=?', $this->uid);
		$this->db->order_by('postdate DESC');
		return parent::get();
	}

	//--------------------------------------------------------------------------

	public function validation($rule)
	{
		$price = $this->price($rule);
		if ( ! $price) return TRUE;

		if ($this->uid == $this->user->id)
		{
			$score = $this->user->score;
		}
		else
		{
			$score = $this->db->select('score')->where('users.id=?', $this->uid)->get('users')->row()->score;
		}

		return ($score + $price) >= 0;
	}

	//--------------------------------------------------------------------------
}