
function completeDEA(automaton){
  var a = automaton; // use the same automaton directly

  // find an existing trap state
  var trapID = -1;
  var trapCreated = false;
  for(var i=0; i < a.States.length; i++){
    if(a.States[i].Final) continue; // trap state cannot be a final state
    if(a.States[i].Transitions.length == 0 || 
      (a.States[i].Transitions.length == 1 && a.States[i].Transitions[0].Target == a.States[i].ID)){
      // state has none transitions or only one to it self
      // use this state as trap state
      trapID = a.States[i].ID;
    }
  }
  // if no trap state found, create one
  if(trapID == -1){
    var trapName = "TRAP";
    var changed = true;
    var trapIDCounter = 1;
    // find a proper name which is unused yet
    while(changed){
      changed = false;
      for(var i=0; i < a.States.length; i++){
        if(a.States[i].Name == trapName){
          trapName = "TRAP"+trapIDCounter++;
          changed = true;
          break;
        }
      }
    }
    // find a new, unused ID for the state 
    for(var z=0; z < a.States.length; z++){ 
      trapID = Math.max(trapID, a.States[z].ID);
    }
    trapID = trapID + 1; // use next number
    // add the state to automaton
    a.States.push({ID:trapID, Name:trapName, x:0, y:0, Final:false, Radius:30, Transitions:[]});
    trapCreated = true;
  }
  
  // we have a trap state now, create all transitions with missing labels to it
  for(var i=0; i < a.States.length; i++){
    var labels = a.Alphabet.slice(0); // copy entire alphabet array
    // remove already used characters
    for(var z=0; z < a.States[i].Transitions.length; z++){
      for(var x=0; x < a.States[i].Transitions[z].Labels.length; x++){
        var p = labels.indexOf(a.States[i].Transitions[z].Labels[x]);
        if(p != -1) labels.splice(p,1); // remove character from the list
      }
    }

    // there are missing labels, so add a transition to trap state
    if(labels.length > 0){
      var transition = null;
      // check if we already have a transition to trap
      for(var z=0; z < a.States[i].Transitions.length; z++){
        if(a.States[i].Transitions[z].Target == trapID){
          transition = a.States[i].Transitions[z];
          break;
        }
      }
      // create transition if not exists
      if(!transition){
        transition = {"Source":a.States[i].ID,"Target":trapID,"x":0,"y":0, Labels:[]};
        a.States[i].Transitions.push(transition);  
      }  
      // add labels to transition
      for(var z=0; z < labels.length; z++){
        transition.Labels.push(labels[z]);
      }
      transition.Labels.sort(); // sort alphabetically
    }
  }

  // auto layout trap state if created this time
  if(trapCreated){
    var r = autoLayoutAutomaton(a,false,false,trapID);
    if(r.result == "OK"){
      a = r.automaton;
    }
  }    
  return {"result":"OK", "automaton":a, "trapID":trapID};
}

////////////////////////////////////////////////////////////////////////////////////////
// Old version from ralf with errors 
////////////////////////////////////////////////////////////////////////////////////////

function completeLinkedDEA(dea, trap){
	function nextID(dea){
		return Array.from(dea.States.values()).reduce((res, s)=> {
			if (s.ID > res)
				res = s.ID;
			return res;
		},0)+1;
	}
	function trapName(dea){
		var trapname = "TRAP";
		var names = Array.from(dea.States.values()).reduce((res,s)=> { res.push(s.Name); return res; }, []);
		cnt = 1;
		while (names.find((n)=>n === trapname) !== undefined){
			trapname = "TRAP"+cnt;
			cnt++;
		}
		return trapname;
	}
	function addTrap(dea, trap){
		for (var s of dea.States.values()) {
			var missinglabels = dea.Alphabet.reduce((res,a)=> { res.set(a,a); return res; }, new Map());
			for (var t of s.Out.values()) {
				for (var l of t.Labels.values())
					missinglabels.delete(l);
			}
			if (missinglabels.size > 0) {
				if (trap === undefined){ // trap-state required
					trap = {ID:nextID(dea), Name:trapName(dea), Start:false, Final:false, Radius:s.Radius, Out:new Map(), In:new Map()};
					var trapself = {Source:trap, Target:trap, Labels: dea.Alphabet.reduce((res,a)=> {res.set(a,a); return res; },new Map())};
					trap.In.set(trap.ID, trapself);
					trap.Out.set(trap.ID, trapself);
					dea.States.set(trap.Name, trap);
				}
				var traptrans = {Source:s, Target:trap, Labels: missinglabels};

				if(s.Out.get(trap.ID)){
				  for (var l of missinglabels.values())
  				  s.Out.get(trap.ID).Labels.set(l,l);
				}else
  				s.Out.set(trap.ID, traptrans);
				if(trap.In.get(s.ID)){
				  for (var l of missinglabels.values())
  				  trap.In.get(s.ID).Labels.set(l,l);
				}else
  				trap.In.set(s.ID, traptrans);
			}
		}
		return (trap && trap.x === undefined) ? trap.ID : -1;
	}
	return addTrap(dea, trap);
}

function completeDEARalf(automaton){
	var linkedEA = linkEA(automaton);
	var trap = Array.from(linkedEA.States.values()).find((s)=> {
		if (!s.Final && (s.Out.size === 0 || (s.Out.size === 1 && s.Out.get(s.ID) !== undefined)))
			return s;
	});
	var trapID = completeLinkedDEA(linkedEA, trap);
	return {"result":"OK", "automaton":unlinkEA(linkedEA, true), "trapID":trapID};
}


