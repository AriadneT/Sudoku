function checkEntry() {
    var cell;
    var newErrorMessage;
    for (box = 1; box < 82; box++) {
        cell = document.getElementById('cell' + box).value;
        if ((cell !== '' && !isInt(cell)) || (cell !== '' && (cell < 1 || cell > 9))) {
			alert('Invalid entry');
            break;
        }
    }
}

function isInt(value) {
    return !isNaN(value) && 
        parseInt(Number(value)) == value && 
        !isNaN(parseInt(value, 10));
}