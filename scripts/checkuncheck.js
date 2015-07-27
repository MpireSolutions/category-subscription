checked = false;
function checkedAll () {
	if (checked == false){checked = true}else{checked = false}
for (var i = 0; i < document.getElementById('st_subscriber').elements.length; i++) {
document.getElementById('st_subscriber').elements[i].checked = checked;
}
}
