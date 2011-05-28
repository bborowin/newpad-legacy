// Initialize the price slider: (abs_min,abs_max) set the range endpoints, (cur_min, cur_maxe) set the current values
function InitSlider(cur_min, cur_max)
{
  abs_min = filters.abs_min; 
  abs_max = filters.abs_max;
  filters.cur_min = cur_min;
  filters.cur_max = cur_max;
  
  
	$("#slider").slider({
    range:true,
		min: abs_min,
		max: abs_max,
		step: filters.slider_step,
		values: [cur_min, cur_max],
		orientation: "vertical",
		slide: function(event, ui) {
      min = ui.values[0];
      max = ui.values[1];
      $("#slider").slider("option", "values", [min,max]);
  
      // if slider is moved to minimum or maximum, use global min/max values
      // (slider min and max range covers 90% active postings, global range shows all)
      var mmax = (max == filters.abs_max) ? filters.global_max : max;
      var mmin = (min == filters.abs_min) ? filters.global_min : min;
      filters.cur_min = mmin;
      filters.cur_max = mmax;
      
			$("#amount").text(MakeLabel(mmin,mmax));
      DragSearchArea();
    }
	});
	$("#amount").text(MakeLabel($("#slider").slider("values", 0), $("#slider").slider("values", 1)));
  Print('slider initialized');
}

function MakeLabel(min,max)
{
  var text = "";
  if(filters.global_min == min)
  {
    if(filters.global_max == max)
    {
      text = 'no limit';
    }
    else
    {
      text = '< $' + max;
    }
  }
  else if(filters.global_max == max)
  {
    text = "> $" + min;
  }
  else
  {
    text = "$" + min + " ~ $" + max;
  }
  
  return text;
}
