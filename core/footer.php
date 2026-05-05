  <!-- Modal -->
  <div id="customModal" class="modal-overlay">
    <div class="modal-card">
      <h3 id="modalTitle" class="modal-title">Alert</h3>
      <p id="modalBody" class="modal-body"></p>
      <div id="modalInputWrap" style="display:none;">
        <input type="text" id="modalInput" class="modal-input" placeholder="Enter value...">
      </div>
      <div class="modal-actions">
        <button id="modalBtnCancel" class="btn btn-outline" style="display:none; padding:8px 16px;">Cancel</button>
        <button id="modalBtnOk" class="btn btn-primary" style="padding:8px 24px;">Confirm</button>
      </div>
    </div>
  </div>
</main>

<script>
// Shared Constants
const TOTAL_CONTACTS = <?= isset($total_contacts) ? $total_contacts : 0 ?>;
</script>
<script src="assets/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', initApp);
</script>
</body>
</html>
