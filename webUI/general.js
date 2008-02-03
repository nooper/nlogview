function checkAll(formname, boxname, master)
{
	with (formname) 
	{	
		for (var i=0; i < elements.length; i++) 
		{	
			if (elements[i].type == 'checkbox' && elements[i].name == boxname)
				elements[i].checked = master.checked;
		}
	}
}
