<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// 取得目前使用者 id
$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user_id = $stmt->get_result()->fetch_assoc()['id'];

// 抓所有 user_apis
$stmt = $conn->prepare("SELECT * FROM user_apis WHERE user_id=? ORDER BY id ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$userApis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>API 設定管理</title>
  <!-- Bootstrap 5, AdminLTE, SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
  <?php include 'navbar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container">
        <h1 class="mt-3 mb-3 text-center">API 設定管理</h1>
      </div>
    </div>
    <div class="content">
      <div class="container">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">您的 API 列表</h3>
            <button class="btn btn-success" id="btnAddApi">
              <i class="fas fa-plus"></i> 新增 API
            </button>
          </div>
          <div class="card-body p-0">
            <?php if (empty($userApis)): ?>
              <p class="text-center py-3 mb-0">尚未新增任何 API 設定</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead class="table-dark">
                    <tr>
                      <th>交易所</th>
                      <th>API Key</th>
                      <th>Secret</th>
                      <th>Passphrase</th>
                      <th>權限</th>
                      <th>備註</th>
                      <th class="text-end">操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($userApis as $api): ?>
                    <tr>
                      <td><?= htmlspecialchars($api['exchange_name']) ?></td>
                      <td><?= htmlspecialchars($api['api_key']) ?></td>
                      <td><?= htmlspecialchars($api['api_secret']) ?></td>
                      <td><?= htmlspecialchars($api['api_passphrase']) ?></td>
                      <td><?= htmlspecialchars($api['permission_level']) ?></td>
                      <td><?= nl2br(htmlspecialchars($api['usage_note'])) ?></td>
                      <td class="text-end">
                        <button class="btn btn-sm btn-primary btnEdit" data-id="<?= $api['id'] ?>">
                          <i class="fas fa-edit"></i> 編輯
                        </button>
                        <button class="btn btn-sm btn-danger btnDelete" data-id="<?= $api['id'] ?>">
                          <i class="fas fa-trash-alt"></i> 刪除
                        </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: 新增/編輯 -->
<div class="modal fade" id="apiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="apiForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="apiModalLabel">新增 API</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="api_id" name="api_id">

          <div class="mb-3">
            <label for="exchange_name" class="form-label">交易所名稱</label>
            <input type="text" class="form-control" id="exchange_name" name="exchange_name" required>
          </div>
          <div class="mb-3">
            <label for="api_key" class="form-label">API Key</label>
            <input type="text" class="form-control" id="api_key" name="api_key" required>
          </div>
          <div class="mb-3">
            <label for="api_secret" class="form-label">API Secret</label>
            <input type="text" class="form-control" id="api_secret" name="api_secret" required>
          </div>
          <div class="mb-3">
            <label for="api_passphrase" class="form-label">API Passphrase</label>
            <input type="text" class="form-control" id="api_passphrase" name="api_passphrase">
          </div>
          <div class="mb-3">
            <label for="permission_level" class="form-label">權限等級</label>
            <input type="text" class="form-control" id="permission_level" name="permission_level">
          </div>
          <div class="mb-3">
            <label for="usage_note" class="form-label">備註</label>
            <textarea class="form-control" id="usage_note" name="usage_note" rows="2"></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-primary" id="btnApiSave">儲存</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- JS: Bootstrap, AdminLTE, jQuery, SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(function(){

  // 新增API按鈕
  $('#btnAddApi').on('click', function(){
    // 清空表單
    $('#apiForm')[0].reset();
    $('#api_id').val('');
    $('#apiModalLabel').text('新增 API');
    $('#apiModal').modal('show');
  });

  // 編輯按鈕
  $('.btnEdit').on('click', function(){
    const apiId = $(this).data('id');
    // 透過 Ajax (GET) 向 edit_api.php?id=xx 取得資料
    $.ajax({
      url: 'edit_api.php',
      type: 'GET',
      data: { id: apiId },
      dataType: 'json',
      success: function(res){
        if(res.success){
          // 將取得的資料填入表單
          $('#api_id').val(res.data.id);
          $('#exchange_name').val(res.data.exchange_name);
          $('#api_key').val(res.data.api_key);
          $('#api_secret').val(res.data.api_secret);
          $('#api_passphrase').val(res.data.api_passphrase);
          $('#permission_level').val(res.data.permission_level);
          $('#usage_note').val(res.data.usage_note);

          $('#apiModalLabel').text('編輯 API');
          $('#apiModal').modal('show');
        } else {
          Swal.fire('錯誤', res.error || '取得資料失敗', 'error');
        }
      },
      error: function(){
        Swal.fire('錯誤', '與伺服器連線失敗', 'error');
      }
    });
  });

  // 儲存 (表單提交) -> POST 到 save_api.php
  $('#apiForm').on('submit', function(e){
    e.preventDefault();
    const formData = $(this).serialize(); // 取得表單資料

    $.ajax({
      url: 'save_api.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(res){
        if(res.success){
          Swal.fire({
            icon: 'success',
            title: '儲存成功',
            confirmButtonText: '確定'
          }).then(()=>{
            location.reload();
          });
        } else {
          Swal.fire('錯誤', res.error || '儲存失敗', 'error');
        }
      },
      error: function(){
        Swal.fire('錯誤','無法連線伺服器','error');
      }
    });
  });

  // 刪除按鈕
  $('.btnDelete').on('click', function(){
    const apiId = $(this).data('id');
    Swal.fire({
      icon: 'warning',
      title: '確認刪除？',
      text: '刪除後將無法復原！',
      showCancelButton: true,
      confirmButtonText: '確定刪除',
      cancelButtonText: '取消'
    }).then((result)=>{
      if(result.isConfirmed){
        // GET 到 delete_api.php?id=xx
        $.ajax({
          url: 'delete_api.php',
          type: 'GET',
          data: { id: apiId },
          dataType: 'json',
          success: function(res){
            if(res.success){
              Swal.fire('刪除成功','', 'success').then(()=>{
                location.reload();
              });
            } else {
              Swal.fire('錯誤', res.error || '刪除失敗','error');
            }
          },
          error: function(){
            Swal.fire('錯誤','無法連線伺服器','error');
          }
        });
      }
    });
  });

});
</script>

</body>
</html>
