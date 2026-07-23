(function () {
  function renumberRows(repeater) {
    repeater.querySelectorAll("[data-astt-row]").forEach(function (row, index) {
      row.querySelectorAll("input").forEach(function (input) {
        input.name = input.name.replace(/astt_people\[[^\]]+\]/, "astt_people[" + index + "]");
      });
    });
  }

  document.addEventListener("click", function (event) {
    var addButton = event.target.closest("[data-astt-add-row]");
    if (addButton) {
      var repeater = document.querySelector("[data-astt-repeater]");
      var firstRow = repeater ? repeater.querySelector("[data-astt-row]") : null;
      if (!repeater || !firstRow) {
        return;
      }

      var nextRow = firstRow.cloneNode(true);
      nextRow.querySelectorAll("input").forEach(function (input) {
        input.value = "";
      });
      repeater.appendChild(nextRow);
      renumberRows(repeater);
      return;
    }

    var removeButton = event.target.closest("[data-astt-remove-row]");
    if (!removeButton) {
      return;
    }

    var row = removeButton.closest("[data-astt-row]");
    var repeater = removeButton.closest("[data-astt-repeater]");
    if (!row || !repeater || repeater.querySelectorAll("[data-astt-row]").length <= 1) {
      return;
    }

    row.remove();
    renumberRows(repeater);
  });
})();
