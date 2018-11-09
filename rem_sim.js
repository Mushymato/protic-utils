window.onload=function(){
	var REMRate = [];
	var totalRate = 0;
	function loadREMRates(data){
		var items = data['items'];
		for(var rarity of items){
			for(var id of rarity['id_array']){
				REMRate.push({'id' : id, 'rate' : rarity['rate'], 'egg' : rarity['egg']});
				totalRate += rarity['rate'];
			}
		}
	}
	function rand(min, max) {
		return Math.random() * (max - min) + min;
	};
	function rollREM(){
		var randNum = rand(0, totalRate);
		var weightSum = 0;
		console.log(randNum);
		 
		for (var i = 0; i < REMRate.length; i++) {
			weightSum += REMRate[i]['rate'];
			weightSum = +weightSum.toFixed(2);
			console.log(REMRate[i] + " : " + weightSum);
			 
			if (randNum <= weightSum) {
				document.getElementById('roll-result').setAttribute('src', 'https://storage.googleapis.com/mirubot/padimages/jp/full/' + REMRate[i]['id'] + '.png');
				
				var icon = document.createElement("img");
				icon.setAttribute('src', 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/' + REMRate[i]['id'] + '.png');
				document.getElementById('roll-result-list').appendChild(icon);
				
				break;
			}
		}
	}
	$.getJSON('./rem_dbdc.json', loadREMRates);
	document.getElementById('roll-button').addEventListener('click', rollREM);
}