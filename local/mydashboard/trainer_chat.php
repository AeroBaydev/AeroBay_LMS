<?php

require_once('../../config.php');

global $DB, $USER, $PAGE, $OUTPUT;

require_login();

if (!$DB->record_exists('trainer', ['userid' => $USER->id])) {
    redirect(new moodle_url('/mydashboard/index.php'), 'Only trainers can access trainer chat.', null,
        \core\output\notification::NOTIFY_ERROR);
}

$PAGE->set_pagelayout('course');
$PAGE->set_url('/local/mydashboard/trainer_chat.php');
$PAGE->set_title('Student Chat');
$PAGE->set_heading('');
$PAGE->requires->js_call_amd('local_mydashboard/trainerchat', 'init', [[
    'listurl' => (new moodle_url('/local/mydashboard/ajax_chat_trainer_list.php'))->out(false),
    'threadurl' => (new moodle_url('/local/mydashboard/ajax_chat_thread.php'))->out(false),
    'sendurl' => (new moodle_url('/local/mydashboard/ajax_chat_send.php'))->out(false),
    'sesskey' => sesskey(),
]]);

echo $OUTPUT->header();
?>
<script>
(function(){
    var n=document.querySelector('nav.navbar')||document.querySelector('.navbar');
    document.documentElement.style.setProperty('--tc-nav-h',(n?n.offsetHeight:50)+'px');
    document.documentElement.classList.add('tc-app');
})();
</script>
<style>
/* ── Lock viewport: zero outer scroll ── */
html.tc-app, html.tc-app > body { height:100%!important; overflow:hidden!important; margin:0!important; }

/* ── Hide Moodle floating ? help button ── */
html.tc-app .btn-footer-popover,
html.tc-app .footer-popover,
html.tc-app #page-footer,
html.tc-app .btn-footer-communication { display:none!important; }

/* ── Strip Moodle page-header chrome ── */
#page-local-mydashboard-trainer_chat #page-header,
#page-local-mydashboard-trainer_chat .page-header-headings { display:none!important; }
#page-local-mydashboard-trainer_chat #topofscroll,
#page-local-mydashboard-trainer_chat #region-main { padding:0!important; border:0!important; box-shadow:none!important; }

/* ── Chat container: fixed to viewport, below navbar ── */
.tc-page { position:fixed; top:var(--tc-nav-h,50px); left:0; right:0; bottom:0; z-index:5; background:#eef2f7; margin:0; padding:0; color:#172033; display:flex; flex-direction:column; box-sizing:border-box; overflow:hidden; }

/* ── Chat shell (two-panel grid) ── */
.tc-shell { width:100%; flex:1 1 0%; min-height:0; margin:0; padding:0; background:#fff; border:0; border-radius:0; box-shadow:none; overflow:hidden; display:grid; grid-template-columns:360px minmax(0,1fr); }

/* ── Left sidebar ── */
.tc-sidebar { border-right:1px solid #dce3ed; display:flex; flex-direction:column; min-width:0; min-height:0; overflow:hidden; }
.tc-sidebar-head { flex:0 0 auto; padding:20px; border-bottom:1px solid #e7ebf1; background:#fff; }
.tc-title-row { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
.tc-back { width:38px; height:38px; border-radius:11px; border:1px solid #dce3ed; display:grid; place-items:center; color:#334155; text-decoration:none!important; }
.tc-title { font-size:1.15rem; font-weight:800; margin:0; }
.tc-subtitle { color:#64748b; font-size:.75rem; margin:2px 0 0; }
.tc-search { width:100%; border:1px solid #cbd5e1; border-radius:11px; padding:10px 12px; font:inherit; font-size:.82rem; outline:none; box-sizing:border-box; }
.tc-search:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.tc-list { flex:1 1 0%; min-height:0; overflow-y:auto; overflow-x:hidden; }

/* ── Contact items ── */
.tc-contact { width:100%; border:0; border-bottom:1px solid #edf0f4; background:#fff; padding:14px 16px; display:flex; gap:11px; text-align:left; cursor:pointer; }
.tc-contact:hover,.tc-contact.active { background:#eff6ff; }
.tc-avatar { width:43px; height:43px; border-radius:50%; flex:0 0 auto; display:grid; place-items:center; background:#dbeafe; color:#1d4ed8; font-size:.78rem; font-weight:800; }
.tc-contact-copy { min-width:0; flex:1; display:flex; flex-direction:column; gap:5px; }
.tc-contact-top,.tc-contact-bottom { display:flex; align-items:center; justify-content:space-between; gap:8px; min-width:0; }
.tc-contact-top strong { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.83rem; }
.tc-activity { color:#94a3b8; font-size:.61rem; white-space:nowrap; }
.tc-preview { color:#64748b; font-size:.7rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tc-unread { min-width:20px; height:20px; padding:0 6px; border-radius:999px; display:grid; place-items:center; background:#16a34a; color:#fff; font-size:.62rem; font-weight:800; }
.tc-list-status,.tc-thread-status { padding:28px 18px; color:#64748b; font-size:.78rem; text-align:center; }
.tc-list-error { color:#dc2626; }

/* ── Right panel ── */
.tc-main { min-width:0; min-height:0; display:flex; overflow:hidden; position:relative; background:#f1f5f9; }
.tc-empty { margin:auto; text-align:center; color:#64748b; }
.tc-empty i { font-size:2.5rem; color:#94a3b8; margin-bottom:12px; }
.tc-panel { width:100%; height:100%; display:flex; flex-direction:column; min-width:0; min-height:0; overflow:hidden; }

/* ── Chat header (pinned top) ── */
.tc-chat-head { height:72px; flex:0 0 72px; padding:0 20px; display:flex; align-items:center; background:#fff; border-bottom:1px solid #dce3ed; }
.tc-chat-head strong { font-size:.92rem; }
.tc-chat-head span { display:block; color:#64748b; font-size:.68rem; margin-top:3px; }

/* ── Message thread (inner scroll only) ── */
.tc-thread { flex:1 1 0%; min-height:0; overflow-y:auto; overflow-x:hidden; padding:20px; display:flex; flex-direction:column; gap:12px; background-color:#edf2f7; background-image:radial-gradient(rgba(148,163,184,.16) 1px,transparent 1px); background-size:18px 18px; }
.tc-message { max-width:76%; display:flex; flex-direction:column; align-items:flex-start; }
.tc-message.mine { align-self:flex-end; align-items:flex-end; }
.tc-bubble { max-width:100%; width:fit-content; padding:9px 11px; border-radius:4px 13px 13px 13px; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.12); font-size:.79rem; line-height:1.5; white-space:pre-wrap; overflow-wrap:anywhere; }
.tc-message.mine .tc-bubble { background:#dcf8c6; border-radius:13px 4px 13px 13px; }
.tc-time { margin-top:4px; color:#64748b; font-size:.6rem; }
.tc-image { display:block; max-width:260px; max-height:260px; border-radius:9px; object-fit:cover; margin-bottom:7px; }

/* ── Composer (pinned bottom) ── */
.tc-compose-wrap { flex:0 0 auto; background:#fff; border-top:1px solid #dce3ed; padding:8px 12px; display:flex; flex-direction:column; gap:4px; }
.tc-attachment-name,.tc-error { min-height:14px; padding-left:50px; font-size:.64rem; color:#64748b; }
.tc-error { color:#dc2626; }
.tc-form { display:flex; gap:8px; align-items:center; }
.tc-attach,.tc-send { width:42px; height:42px; flex:0 0 42px; align-self:center; border:0; border-radius:11px; display:grid; place-items:center; cursor:pointer; }
.tc-attach { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.tc-input { flex:1 1 auto; align-self:center; min-height:42px; max-height:110px; resize:none; border:1px solid #cbd5e1; border-radius:11px; padding:10px 11px; font:inherit; font-size:.79rem; outline:none; }
.tc-send { color:#fff; background:#16a34a; }
.tc-send:disabled { opacity:.55; cursor:wait; }

/* ── Mobile ── */
@media(max-width:800px){
  .tc-shell { grid-template-columns:64px minmax(0,1fr); }
  .tc-sidebar-head { padding:10px; }
  .tc-title-row { margin-bottom:8px; }
  .tc-title,.tc-subtitle,.tc-contact-copy { display:none; }
  .tc-contact { justify-content:center; padding:10px 4px; }
  .tc-avatar { width:38px; height:38px; }
  .tc-message { max-width:90%; }
}
</style>
<div class="tc-page">
  <div class="tc-shell">
    <aside class="tc-sidebar">
      <header class="tc-sidebar-head">
        <div class="tc-title-row">
          <a class="tc-back" href="<?php echo (new moodle_url('/mydashboard/index.php'))->out(false); ?>" aria-label="Back to dashboard"><i class="fas fa-arrow-left"></i></a>
          <div><h1 class="tc-title">Student Chat</h1><p class="tc-subtitle">Persistent trainer conversations</p></div>
        </div>
        <input class="tc-search" type="search" placeholder="Search students" data-region="trainer-chat-search">
      </header>
      <div class="tc-list" data-region="trainer-chat-list"></div>
    </aside>
    <main class="tc-main">
      <div class="tc-empty" data-region="trainer-chat-empty"><i class="fas fa-comments"></i><div>Select a student to open the conversation.</div></div>
      <section class="tc-panel" data-region="trainer-chat-panel" hidden>
        <header class="tc-chat-head"><div><strong data-region="trainer-chat-student">Student</strong><span>Student conversation</span></div></header>
        <div class="tc-thread" data-region="trainer-chat-thread"></div>
        <div class="tc-compose-wrap">
          <div class="tc-attachment-name" data-region="trainer-chat-attachment-name"></div>
          <form class="tc-form" data-region="trainer-chat-form">
            <input id="trainerChatAttachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,image/jpeg,image/png" hidden>
            <label class="tc-attach" for="trainerChatAttachment" title="Attach image"><i class="fas fa-image"></i></label>
            <textarea class="tc-input" name="message" rows="1" maxlength="4000" placeholder="Type a message..." data-region="trainer-chat-input"></textarea>
            <button class="tc-send" type="submit" data-action="trainer-chat-send" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
          </form>
          <div class="tc-error" data-region="trainer-chat-error"></div>
        </div>
      </section>
    </main>
  </div>
</div>
<?php
echo $OUTPUT->footer();
