<?php
// Bu dosya index.php tarafından çağrıldığı için $conn ve session değişkenleri zaten tanımlı.
// functions.php içindeki time_ago fonksiyonu da kullanılabilir.

$loggedInUserId = $_SESSION['user_id'] ?? null;
$loggedInUserName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$active_chat_sender_name = isset($_GET['sender']) ? trim($_GET['sender']) : null;
$active_chat_sender_display_name = $active_chat_sender_name ? htmlspecialchars($active_chat_sender_name) : "Bir sohbet seçin";

$page_error = '';
$page_success = '';

// Yeni mesaj gönderme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message_content']) && !empty(trim($_POST['message_content'])) && $active_chat_sender_name) {
    $message_to_send = trim($_POST['message_content']);
    $receiver_user_id = null;

    if ($active_chat_sender_name !== 'Sistem') {
        $stmt_receiver_id = $conn->prepare("SELECT id FROM users WHERE name = ? LIMIT 1");
        if ($stmt_receiver_id) {
            $stmt_receiver_id->bind_param("s", $active_chat_sender_name);
            $stmt_receiver_id->execute();
            $stmt_receiver_id->bind_result($receiver_user_id);
            $stmt_receiver_id->fetch();
            $stmt_receiver_id->close();
        }
    }

    if ($receiver_user_id) {
        $subject_for_reply = "RE: " . htmlspecialchars($active_chat_sender_name);
        // messages tablonuzda subject olup olmadığını kontrol edin, yoksa kaldırın veya null geçin.
        // Sizin messages tablonuzda subject vardı.
        $insert_stmt = $conn->prepare("INSERT INTO messages (user_id, sender_name, subject, content, created_at, is_read) VALUES (?, ?, ?, ?, NOW(), FALSE)");
        if ($insert_stmt) {
            $insert_stmt->bind_param("isss", $receiver_user_id, $loggedInUserName, $subject_for_reply, $message_to_send);
            if ($insert_stmt->execute()) {
                header("Location: index.php?page=messages&sender=" . urlencode($active_chat_sender_name));
                exit;
            } else {
                $page_error = "Mesaj gönderilirken bir hata oluştu: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $page_error = "Mesaj gönderme sorgusu hazırlanamadı: " . $conn->error;
        }
    } elseif ($active_chat_sender_name === 'Sistem') {
        $page_error = "Sistem mesajlarına bu arayüzden doğrudan cevap verilemez.";
    } else {
        $page_error = "Mesaj gönderilecek alıcı bulunamadı veya geçerli değil.";
    }
}

// Sol panel için sohbet listesini çek
$conversations = [];
$temp_conversations_data = [];

$stmt_senders_to_me = $conn->prepare("
    SELECT sender_name, MAX(created_at) as last_interaction_time
    FROM messages WHERE user_id = ?
    GROUP BY sender_name
");
if ($stmt_senders_to_me) {
    $stmt_senders_to_me->bind_param("i", $loggedInUserId);
    $stmt_senders_to_me->execute();
    $result = $stmt_senders_to_me->get_result();
    while($row = $result->fetch_assoc()) {
        $temp_conversations_data[$row['sender_name']] = $row['last_interaction_time'];
    }
    $stmt_senders_to_me->close();
} else {
    $page_error .= " Sohbet listesi sorgusu (1) hazırlanamadı: " . $conn->error;
}

$stmt_receivers_from_me = $conn->prepare("
    SELECT u.name as partner_name, MAX(m.created_at) as last_interaction_time
    FROM messages m JOIN users u ON m.user_id = u.id
    WHERE m.sender_name = ?
    GROUP BY u.name
");
if ($stmt_receivers_from_me) {
    $stmt_receivers_from_me->bind_param("s", $loggedInUserName);
    $stmt_receivers_from_me->execute();
    $result = $stmt_receivers_from_me->get_result();
    while($row = $result->fetch_assoc()) {
        if (!isset($temp_conversations_data[$row['partner_name']]) || strtotime($temp_conversations_data[$row['partner_name']]) < strtotime($row['last_interaction_time'])) {
            $temp_conversations_data[$row['partner_name']] = $row['last_interaction_time'];
        }
    }
    $stmt_receivers_from_me->close();
} else {
    $page_error .= " Sohbet listesi sorgusu (2) hazırlanamadı: " . $conn->error;
}

arsort($temp_conversations_data);

foreach ($temp_conversations_data as $partner_name => $last_interaction_time_str) {
    $last_snippet_content = 'Henüz mesaj yok.';
    $unread_count = 0;

    $stmt_lm_snippet = $conn->prepare("
        SELECT content FROM messages
        WHERE (user_id = ? AND sender_name = ?) OR (sender_name = ? AND user_id = (SELECT id FROM users WHERE name = ? LIMIT 1))
        ORDER BY created_at DESC LIMIT 1
    ");
    if ($stmt_lm_snippet) {
        $stmt_lm_snippet->bind_param("isss", $loggedInUserId, $partner_name, $loggedInUserName, $partner_name);
        $stmt_lm_snippet->execute();
        $stmt_lm_snippet->bind_result($snippet_res);
        if ($stmt_lm_snippet->fetch()) {
            $last_snippet_content = $snippet_res;
        }
        $stmt_lm_snippet->close();
    } else {
         $page_error .= " Son mesaj metni sorgusu hazırlanamadı: " . $conn->error;
    }

    $stmt_uc = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND sender_name = ? AND is_read = FALSE");
    if ($stmt_uc) {
        $stmt_uc->bind_param("is", $loggedInUserId, $partner_name);
        $stmt_uc->execute();
        $stmt_uc->bind_result($unread_count);
        $stmt_uc->fetch();
        $stmt_uc->close();
    } else {
        $page_error .= " Okunmamış mesaj sayısı sorgusu hazırlanamadı: " . $conn->error;
    }

    $conversations[] = [
        'partner_name' => $partner_name,
        'last_message_time_raw' => $last_interaction_time_str,
        'last_message_time_ago' => time_ago($last_interaction_time_str),
        'last_message_snippet' => htmlspecialchars(mb_substr($last_snippet_content, 0, 35)) . (mb_strlen($last_snippet_content) > 35 ? '...' : ''),
        'unread_count' => $unread_count
    ];
}

// Sağ panel için aktif sohbetin mesajlarını çek
$chat_messages = [];
$chat_partner_user_id_for_message_area = null;

if ($active_chat_sender_name) {
    if ($active_chat_sender_name !== 'Sistem') {
        $stmt_chat_partner_id = $conn->prepare("SELECT id FROM users WHERE name = ? LIMIT 1");
        if ($stmt_chat_partner_id) {
            $stmt_chat_partner_id->bind_param("s", $active_chat_sender_name);
            $stmt_chat_partner_id->execute();
            $stmt_chat_partner_id->bind_result($chat_partner_user_id_for_message_area);
            $stmt_chat_partner_id->fetch();
            $stmt_chat_partner_id->close();
        }
    }

    // Sizin messages tablonuzda 'subject' sütunu var, ancak mesaj listesinde göstermiyoruz, isterseniz ekleyebilirsiniz.
    $stmt_msg_list = $conn->prepare("
        SELECT id, user_id, sender_name, content, created_at 
        FROM messages
        WHERE (user_id = ? AND sender_name = ?) OR (sender_name = ? AND user_id = ?)
        ORDER BY created_at ASC
    ");

    if ($stmt_msg_list) {
        $effective_chat_partner_id = $chat_partner_user_id_for_message_area ? $chat_partner_user_id_for_message_area : -1;

        $stmt_msg_list->bind_param("issi", $loggedInUserId, $active_chat_sender_name, $loggedInUserName, $effective_chat_partner_id);
        $stmt_msg_list->execute();
        $result_messages = $stmt_msg_list->get_result();
        while ($row = $result_messages->fetch_assoc()) {
            $chat_messages[] = $row;
        }
        $stmt_msg_list->close();

        // Seçili sohbetteki okunmamış mesajları okundu olarak işaretle
        // 'read_at = NOW()' kısmı kaldırıldı, çünkü messages tablonuzda 'read_at' sütunu yok.
        $stmt_mark_read = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE user_id = ? AND sender_name = ? AND is_read = FALSE");
        if ($stmt_mark_read) {
            $stmt_mark_read->bind_param("is", $loggedInUserId, $active_chat_sender_name);
            $stmt_mark_read->execute();
            $stmt_mark_read->close();
            foreach ($conversations as $key => $conv) {
                if ($conv['partner_name'] === $active_chat_sender_name) {
                    $conversations[$key]['unread_count'] = 0;
                    break;
                }
            }
        } else {
            $page_error .= " Mesajları okundu işaretleme sorgusu hazırlanamadı: " . $conn->error;
        }
    } else {
        $page_error .= " Mesaj listesi sorgusu (sağ panel) hazırlanamadı: " . $conn->error;
    }
}

?>

<h1>Mesajlar</h1>

<?php if ($page_error): ?>
    <div class="alert alert-danger"><?= nl2br(htmlspecialchars(trim($page_error))) ?></div>
<?php endif; ?>
<?php if ($page_success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($page_success) ?></div>
<?php endif; ?>

<div class="messages-container">
    <div class="chat-list-panel">
        <?php if (!empty($conversations)): ?>
            <?php foreach ($conversations as $conv): ?>
                <a href="index.php?page=messages&sender=<?= urlencode($conv['partner_name']) ?>"
                   class="chat-list-item <?= ($active_chat_sender_name == $conv['partner_name']) ? 'active' : '' ?>">
                    <div class="avatar">
                        <?= htmlspecialchars(mb_substr($conv['partner_name'], 0, 2)) ?>
                    </div>
                    <div class="details">
                        <div class="name"><?= htmlspecialchars($conv['partner_name']) ?></div>
                        <div class="last-message"><?= $conv['last_message_snippet'] ?></div>
                    </div>
                    <div class="meta">
                        <div class="time" title="<?= htmlspecialchars($conv['last_message_time_raw'])?>"><?= $conv['last_message_time_ago'] ?></div>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <div class="unread-count"><?= $conv['unread_count'] ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-chats">Henüz bir sohbetiniz bulunmuyor.</p>
        <?php endif; ?>
    </div>

    <div class="chat-window-panel">
        <?php if ($active_chat_sender_name): ?>
            <div class="chat-header">
                <div class="sender-info">
                     <div class="avatar-header">
                        <?= htmlspecialchars(mb_substr($active_chat_sender_display_name, 0, 2)) ?>
                    </div>
                    <h3><?= $active_chat_sender_display_name ?></h3>
                </div>
            </div>
            <div class="chat-messages-area" id="chatMessagesArea">
                <?php if (!empty($chat_messages)): ?>
                    <?php foreach ($chat_messages as $msg): ?>
                        <?php
                        $message_class = 'received';
                        if ($msg['sender_name'] == $loggedInUserName) {
                            $message_class = 'sent';
                        }
                        ?>
                        <div class="message-bubble <?= $message_class ?>">
                            <div class="content">
                                <p><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                            </div>
                            <div class="timestamp" title="<?= htmlspecialchars($msg['created_at']) ?>"><?= time_ago($msg['created_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-messages-selected">Bu sohbette henüz mesaj bulunmuyor.</p>
                <?php endif; ?>
            </div>
            <div class="chat-input-area">
                <?php if ($active_chat_sender_name !== 'Sistem' && $chat_partner_user_id_for_message_area): ?>
                <form action="index.php?page=messages&sender=<?= urlencode($active_chat_sender_name) ?>" method="POST">
                    <textarea name="message_content" placeholder="Mesajınızı buraya yazın..." required></textarea>
                    <button type="submit" title="Gönder"><i class="fas fa-paper-plane"></i></button>
                </form>
                <?php elseif ($active_chat_sender_name === 'Sistem'): ?>
                 <p class="system-message-reply-info">Sistem mesajlarına buradan cevap verilemez.</p>
                <?php else: ?>
                 <p class="system-message-reply-info">Bu kişiye mesaj gönderilemiyor.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-chat-selected">
                <i class="fas fa-comments fa-3x"></i>
                <p>Lütfen görüntülemek için sol taraftan bir sohbet seçin.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatArea = document.getElementById('chatMessagesArea');
    if (chatArea) {
        chatArea.scrollTop = chatArea.scrollHeight;
    }
});
</script>