function sumRates(){
	var sumSelected = 0;
	var rateGroups = document.getElementsByClassName("rate-group");
	for(var rg of rateGroups){
		var rate = parseFloat(rg.getAttribute("data-rate"));
		var padCheckbox = rg.querySelectorAll('input[type="checkbox"][id^="pad-cb-"]');
		for(var cb of padCheckbox){
			if(cb.checked){
				sumSelected += rate;
			}
		}
	}
	document.getElementById("total-rate").innerHTML = Number(sumSelected).toFixed(2);
}
window.onload=function(){
	var padCheckbox = document.querySelectorAll('input[type="checkbox"][id^="pad-cb-"]');
	for(var cb of padCheckbox){
		cb.addEventListener("change", sumRates);
	}
	document.getElementById("clear-selected").addEventListener("click", 
	function(){
		document.getElementById("total-rate").innerHTML = "0.00";
	}
	);
}