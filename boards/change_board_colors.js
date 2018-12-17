var orb_list = ["R", "B", "G", "L", "D"];
function changeColor(base, target){
	//$("div[data-orb='" + base + "']").css("background-image", "url(img/" + target + ".png)");
	for(let orb of orb_list){
		$("[data-orb='" + base + "']").removeClass(orb);
	}
	$("[data-orb='" + base + "']").addClass(target);
}
function refreshColor(orb){
	changeColor(orb, window.localStorage.getItem("att-" + orb));
}
function addChangeColorListeners(dataAttName){
	$("input[" + dataAttName + "]").each(function(index) {
		$(this).on("click", function(){
			var data = $(this).attr("data-attribute").split("-");
			window.localStorage.setItem("att-" + data[0], data[1]);
			refreshColor(data[0]);
		});
	});
}
function refreshAllColors(){
	for(let orb of orb_list){
		if(window.localStorage.getItem("att-" + orb) === null){
			window.localStorage.setItem("att-" + orb, orb);
		}
		var target = window.localStorage.getItem("att-" + orb);
		changeColor(orb, target);
		$("input[data-attribute='" + orb + "-" + target + "']").prop("checked", true);
	}
}