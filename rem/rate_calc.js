function sumRates(){
	var machines = document.querySelectorAll("form[id^='rem-']");
	for(var rem of machines){
		var sumSelected = 0;
		var remID = rem.getAttribute("id");
		var rateGroups = rem.querySelectorAll("#"+remID+" div.rate-group");
		for(var rg of rateGroups){
			var rate = parseFloat(rg.getAttribute("data-rate"));
			var padCheckbox = rg.querySelectorAll("#"+remID+" input[type='checkbox'].rem-icon-cb");
			for(var cb of padCheckbox){
				if(cb.checked){
					sumSelected += rate;
				}
			}
		}
		rem.querySelector("#"+remID+" span.total-rate").innerHTML = Number(sumSelected).toFixed(2);
		if(sumSelected != 0){
			var stones = rem.querySelector("#"+remID+" input.stone-count");
			var rolls = Math.floor(parseInt(stones.value) / parseInt(stones.getAttribute("data-cost")));
			if(rolls != 0 && rolls != NaN){
				rem.querySelector("#"+remID+" span.cumulative-rate").innerHTML = Number((1 - Math.pow(1 - sumSelected/100, rolls))*100).toFixed(2);
			}
		}else{
			rem.querySelector("#"+remID+" span.cumulative-rate").innerHTML = "0.00";
		}
	}
}
function showRegion(){
	var region = window.localStorage.getItem("region");
	document.getElementById("egg-machine-region").innerHTML = region;
	var regionDivs = document.querySelectorAll("div[id^='region-']");
	for(var rd of regionDivs){
		if(rd.getAttribute("id") === "region-" + region){
			rd.style.display = "block";
			rd.style.zIndex = "1";
		}else{
			rd.style.display = "none";
			rd.style.zIndex = "0";
		}
	}
}
window.onload=function(){
	if(window.localStorage.getItem("region") === null){
		window.localStorage.setItem("region", 'JP');
	}
	showRegion();
	document.getElementById("egg-machine-region").addEventListener("click",
		function(){
			if(window.localStorage.getItem("region") === 'NA'){
				window.localStorage.setItem("region", 'JP');
			}else{
				window.localStorage.setItem("region", 'NA');
			}
			showRegion();
		}
	);

	var padCheckbox = document.querySelectorAll('input[type="checkbox"].rem-icon-cb');
	for(var cb of padCheckbox){
		cb.addEventListener("change", sumRates);
	}
	var stoneCount = document.getElementsByClassName("stone-count");
	for(var txt of stoneCount){
		txt.addEventListener("change", sumRates);
	}
	
	var clearBtns = document.getElementsByClassName("clear-selected");
	for(var btn of clearBtns){
		btn.addEventListener("click", 
			function(){
				document.querySelector("#"+event.srcElement.getAttribute("data-machineid")+" span.total-rate").innerHTML = "0.00";
				document.querySelector("#"+event.srcElement.getAttribute("data-machineid")+" span.cumulative-rate").innerHTML = "0.00";
			}
		);
	}
	
	var selectGroup = document.getElementsByClassName("select-group");
	for(var select of selectGroup){
		select.addEventListener("change", 
			function(){
				var padCheckbox = document.querySelectorAll("div[data-rategroupid='"+event.srcElement.id+"'] .rem-icon-cb");
				for(var cb of padCheckbox){
					cb.checked = event.srcElement.checked;
				}
				sumRates();
			}
		);
	}

	
	var tabLinks = document.getElementsByClassName("egg-machine-tab-link");
	for(var link of tabLinks){
		link.addEventListener("click", 
			function(){
				var target = event.srcElement.getAttribute("data-machineid");
				var machines = document.querySelectorAll("#region-" + window.localStorage.getItem("region") + " form[id*='em-']");
				for(var machine of machines){
					if(machine.getAttribute("id") === target){
						machine.style.opacity = "1";
						machine.style.visibility = "visible";
					}else{
						machine.style.opacity = "0";
						machine.style.visibility = "hidden";
					}
				}
			}
		);
	}
}