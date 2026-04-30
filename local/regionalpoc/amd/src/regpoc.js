function openModal(modalId) {
  var modal = document.getElementById(modalId);
  modal.style.display = "block";
}

function closeModal(modalId) {
  var modal = document.getElementById(modalId);
  modal.style.display = "none";
}

// Close the modal if the user clicks anywhere outside of it
window.onclick = function (event) {
  var modals = document.getElementsByClassName("modal");
  for (var i = 0; i < modals.length; i++) {
    var modal = modals[i];
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }
};

$(document).ready(function () {

  $(document).on('click', '.close-modal', function(){
    $(".modal").hide();
  });

  $(".appointment").on("click", function () {
    let id = $(this).attr("data-id");
    let userid = $(this).attr("data-userid");

    $.ajax({
      url: "ajax.php",
      method: "post",
      data: { id, userid },
      dataType: "json",
      async: true,
      success: function (data) {
        $(".modal").html(data.html);
        $(".modal").show();
      },
    });
  });
});
