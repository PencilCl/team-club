<?php
class Project_model extends CI_Model {
  public function __construct() {
    parent::__construct();

    $this->load->database();
    $this->load->library('session');
    $this->load->model('Team_model', 'team');
  }

  public function setIcon($pid, $icon) {
    $uid = $this->session->user['uid'];
    $sql = "CALL set_icon(?, ?, ?)";
    $row = $this->db->query($sql, array($uid, $pid, $icon))->row();
    if (isset($row) && $row->res === '1') {
      return null;
    }
    return '权限不足';
  }

  public function setColor($pid, $color) {
    $uid = $this->session->user['uid'];
    $sql = "CALL set_color(?, ?, ?)";
    $row = $this->db->query($sql, array($uid, $pid, $color))->row();
    if (isset($row) && $row->res === '1') {
      return null;
    }
    return '权限不足';
  }

  public function getSettingInfo($pid) {
    $uid = $this->session->user['uid'];
    $sql = "CALL get_project_setting_info(?, ?)";
    $row = $this->db->query($sql, array($uid, $pid))->row();
    if (isset($row)) {
        return array('error' => null, 'data' => $row);
    }
    return array('error' => '获取信息失败');
  }

  public function saveSettingInfo($pid, $isPrivate, $name, $description) {
    $uid = $this->session->user['uid'];
    $sql = "CALL save_project_setting_info(?, ?, ?, ?, ?)";
    $row = $this->db->query($sql, array($uid, $pid, $isPrivate, $name, $description))->row();
    if (isset($row) && $row->res === '1') {
      return null;
    }
    return '权限不足';
  }

  public function getInfo($pid) {
    // 获取项目信息
    $sql = "SELECT dir_id as dirId, doc_dir_id as docDirId, name, description, private, tid FROM Project WHERE pid = ?";
    $query = $this->db->query($sql, array($pid));
    $project = $query->row();
    if (!isset($project)) {
      return array('error' => '获取信息失败');
    }
    // 检查是否有权限查看项目
    if ($project->private === '1') {
      $uid = $this->session->user['uid'];
      $sql = "SELECT uid FROM TeamMember WHERE tid = ? AND uid = ? AND accept = 1";
      $query = $this->db->query($sql, array($project->tid, $uid));
      if ($query->num_rows() <= 0) {
        return array('error' => '权限不足');
      }
    }
    // 获取未完成任务列表
    $sql = "SELECT task_id as taskId, task_list_id as taskListId, name, doing, uid, finished FROM Task WHERE pid = ? AND deleted = 0 AND finished is null";
    $query = $this->db->query($sql, array($pid));
    $tasks = $query->result_array();
    // 获取任务清单列表
    $sql = "SELECT task_list_id as taskListId, name FROM TaskList WHERE pid = ? AND deleted = 0 AND archived is null";
    $query = $this->db->query($sql, array($pid));
    $taskLists = $query->result_array();
    // 获取讨论列表
    $sql = "SELECT did, u.uid, u.avatar, u.name, topic, `date` FROM Discussion d, User u WHERE d.uid = u.uid AND d.pid = ? AND deleted = 0";
    $query = $this->db->query($sql, array($pid));
    $discussions = $query->result_array();

    return array(
      'error' => null,
      'data' => array(
        'tasks' => $tasks,
        'taskLists' => $taskLists,
        'discussions' => $discussions,
        'project' => $project
      )
    );
  }

  public function archivedTaskList($pid) {
    $uid = $this->session->user['uid'];
    $sql = "CALL get_archived_taskList(?, ?)";
    $query = $this->db->query($sql, array($uid, $pid));
    return array('error' => null, 'data' => $query->result_array());
  }

  public function finishedTasks($pid) {
    $uid = $this->session->user['uid'];
    $sql = "CALL get_finished_tasks(?, ?)";
    $query = $this->db->query($sql, array($uid, $pid));
    return array('error' => null, 'data' => $query->result_array());
  }

  public function checkAuth($pid) {
    $uid = $this->session->user['uid'];
    $sql = "SELECT * FROM Project p, TeamMember tm WHERE p.pid = ? AND p.tid = tm.tid AND tm.uid = ? AND tm.accept = 1";
    $query = $this->db->query($sql, array($pid, $uid));
    return $query->num_rows() > 0;
  }
}