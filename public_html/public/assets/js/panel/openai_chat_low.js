const streamUrl = $('meta[name=stream-url]').attr('content');

let selectedPrompt = -1;
let promptsData = [];
let favData = [];
let searchString = "";
let filterType = 'all';

let imagePath = [];

var prompt_images = [];

function updateFav(id) {
	$.ajax({
		type: "post",
		url: '/dashboard/user/openai/chat/update-prompt',
		data: {
			id: id
		},
		success: function (data) {
			favData = data;
			updatePrompts(promptsData);
		},
		error: function () {

		}
	});
}

function updatePrompts(data) {

	const $prompts = $('#prompts');

	$prompts.empty();

	if (data.length == 0) {
		$("#no_prompt").removeClass("hidden");
	} else {
		$("#no_prompt").addClass("hidden");
	}

	for (let i = 0; i < data.length; i++) {

		let isFav = favData.filter(item => item.item_id == data[i].id).length;

		let title = data[i].title.toLowerCase();
		let prompt = data[i].prompt.toLowerCase();
		let searchStr = searchString.toLowerCase();

		if (searchStr.replace(/\s/g, '') === '') {
		}

		if (data[i].id == selectedPrompt) {
			if (title.includes(searchStr) || prompt.includes(searchStr)) {
				if ((filterType == 'fav' && isFav != 0) || filterType != 'fav') {
					let prompt = document.querySelector("#selected_prompt").content.cloneNode(true);
					const favbtn = prompt.querySelector('.favbtn');
					prompt.querySelector('.prompt_title').innerHTML = data[i].title;
					prompt.querySelector('.prompt_text').innerHTML = data[i].prompt;
					favbtn.setAttribute('id', data[i].id);

					if (isFav != 0) {
						favbtn.classList.add('active');
					}
					else {
						favbtn.classList.remove('active');
					}

					$prompts.append(prompt);
				} else {
					selectedPrompt = -1;
				}
			} else {
				selectedPrompt = -1;
			}
		} else {
			if (title.includes(searchStr) || prompt.includes(searchStr)) {
				if ((filterType == 'fav' && isFav != 0) || filterType != 'fav') {
					let prompt = document.querySelector("#unselected_prompt").content.cloneNode(true);
					const favbtn = prompt.querySelector('.favbtn');
					prompt.querySelector('.prompt_title').innerHTML = data[i].title;
					prompt.querySelector('.prompt_text').innerHTML = data[i].prompt;
					favbtn.setAttribute('id', data[i].id);

					if (isFav != 0) {
						favbtn.classList.add('active');
					}
					else {
						favbtn.classList.remove('active');
					}

					$prompts.append(prompt);
				}
			}
		}
	}
	let favCnt = favData.length;
	let perCnt = data.length;

	if (favCnt == 0) {
		$("#fav_count")[0].innerHTML = ''
	} else {
		$("#fav_count")[0].innerHTML = favCnt;
	}

	if (perCnt == 0 || perCnt == undefined) {
		$("#per_count")[0].innerHTML = '';
	} else {
		$("#per_count")[0].innerHTML = perCnt
	};
}

function searchStringChange(e) {
	searchString = $("#search_str").val();
	console.log(searchString);
	updatePrompts(promptsData);
}

function openNewImageDlg(e) {
	$('#selectImageInput').click();
}

function updatePromptImages() {
	$("#chat_images").empty();
	if (prompt_images.length == 0) {
		$("#chat_images").addClass('hidden');
		$('.split_line').addClass('hidden');
		return;
	}
	$("#chat_images").removeClass('hidden');
	$('.split_line').removeClass('hidden');
	for (let i = 0; i < prompt_images.length; i++) {
		let new_image = document.querySelector("#prompt_image").content.cloneNode(true);
		$(new_image.querySelector('img')).attr('src', prompt_images[i]);
		$(new_image.querySelector('.prompt_image_close')).attr('index', i);
		$(document.querySelector("#chat_images")).append(new_image);
	}
	let new_image_btn = document.querySelector("#prompt_image_add_btn").content.cloneNode(true);
	document.querySelector("#chat_images").append(new_image_btn);
	$(".promt_image_btn").on('click', function (e) {
		e.preventDefault();
		$("#chat_add_image").click();
	});
	$('.prompt_image_close').on('click', function (e) {
		prompt_images.splice($(this).attr('index'), 1);
		updatePromptImages();
	});
}

function addImagetoChat(data) {

	if (prompt_images.filter(item => item == data).length == 0) {
		prompt_images.push(data);
		updatePromptImages();
	}
}

function updatePromptPdfs(name) {
	$("#chat_pdfs").empty();
	if (pdf == undefined) {
		$("#chat_pdfs").addClass('hidden');
		$('.split_line').addClass('hidden');
		return;
	}
	$("#chat_pdfs").removeClass('hidden');
	$('.split_line').removeClass('hidden');
	let new_pdf = document.querySelector("#prompt_pdf").content.cloneNode(true);
	$(new_pdf.querySelector('label')).text(name);
	$(document.querySelector("#chat_pdfs")).append(new_pdf);
	// let new_image_btn = document.querySelector("#prompt_image_add_btn").content.cloneNode(true);
	// document.querySelector("#chat_Pdfs").append(new_image_btn);
	// $(".promt_image_btn").on('click', function (e) {
	// 	e.preventDefault();
	// 	$("#chat_add_image").click();
	// });
	$('.prompt_pdf_close').on('click', function (e) {
		pdf = undefined;
		updatePromptPdfs('');
	});
}

function initChat() {
	var mediaRecorder;
	let audioBlob;
	var chunks = [];
	var stream_;

	prompt_images = [];

	$("#scrollable_content").animate({ scrollTop: 1000 }, 200);
	// Start recording when the button is pressed
	$('#voice_record_button').click(function (e) {
		chunks = [];
		navigator.mediaDevices.getUserMedia({ audio: true })
			.then(function (stream) {
				stream_ = stream;
				mediaRecorder = new MediaRecorder(stream);
				$("#voice_record_button").addClass("hidden");
				$("#voice_record_stop_button").removeClass('hidden');
				isRecord = true;
				console.log(mediaRecorder);
				mediaRecorder.ondataavailable = function (e) {
					console.log(e.data);
					chunks.push(e.data);
				}
				mediaRecorder.start();
			}).catch(function (err) {
				console.log('The following error occurred: ' + err);
				toastr.warning('Audio is not allowed');
			});;

		$('#voice_record_stop_button').click(function (e) {
			e.preventDefault();
			$("#voice_record_button").removeClass("hidden");
			$("#voice_record_stop_button").addClass('hidden');
			isRecord = false;
			mediaRecorder.onstop = function (e) {
				var blob = new Blob(chunks, { 'type': 'audio/mp3' });

				var formData = new FormData();
				var fileOfBlob = new File([blob], 'audio.mp3');
				formData.append("file", fileOfBlob);

				chunks = [];

				$.ajax({
					url: '/dashboard/user/openai/chat/transaudio',
					type: 'POST',
					data: formData,
					contentType: false,
					processData: false,
					success: function (response) {
						if (response.length >= 4) {
							$("#prompt").val(response);
						}
					},
					error: function (error) {
						// Handle the error response
					}
				});
			}
			mediaRecorder.stop();
			stream_.getTracks() // get all tracks from the MediaStream
				.forEach(track => track.stop()); // stop each of them
		})
	});
	$("#btn_add_new_prompt").on('click', function (e) {
		prompt_title = $("#new_prompt_title").val();
		prompt = $("#new_prompt").val();

		if (prompt_title.trim() == '') {
			toastr.warning('Please input title');
			return;
		}

		if (prompt.trim() == '') {
			toastr.warning('Please input prompt');
			return;
		}

		$.ajax({
			type: "post",
			url: '/dashboard/user/openai/chat/add-prompt',
			data: {
				title: prompt_title,
				prompt: prompt
			},
			success: function (data) {
				promptsData = data;
				updatePrompts(data);
				$(".custom__popover__back").addClass("hidden");
				$("#custom__popover").removeClass('custom__popover__wrapper');
			},
			error: function () {

			}
		});
	});

	$('#add_btn').on('click', function (e) {
		$("#custom__popover").addClass('custom__popover__wrapper');
		$(".custom__popover__back").removeClass("hidden");
		e.stopPropagation();
	})

	$(".custom__popover__back").on('click', function () {
		$(this).addClass("hidden");
		$("#custom__popover").removeClass('custom__popover__wrapper');
	})

	$('#prompt_library').on('click', function (e) {

		e.preventDefault();

		$("#prompts").empty();

		$.ajax({
			type: "post",
			url: '/dashboard/user/openai/chat/prompts',
			success: function (data) {
				filterType = "all";
				promptsData = data.promptData;
				console.log('Update');
				favData = data.favData;
				updatePrompts(data.promptData);
				$("#modal").addClass('modal__wrapper');
				$(".modal__back").removeClass("hidden");
			},
			error: function () {

			}
		});
		e.stopPropagation();
	})

	$(".modal__back").on('click', function () {
		$(this).addClass("hidden");
		$("#modal").removeClass('modal__wrapper');
	})

	$(document).on('click', '.prompt', function () {
		const $promptInput = $('#prompt');
		selectedPrompt = Number($(this.querySelector('.favbtn')).attr('id'));
		$promptInput.val(promptsData.filter(item => item.id == selectedPrompt)[0].prompt);
		$(".modal__back").addClass("hidden");
		$("#modal").removeClass('modal__wrapper');
		selectedPrompt = -1;
		// updatePrompts(promptsData);
		console.log($(this.querySelector('.favbtn')).attr('id'));
		$promptInput.css('height', "5px");
		$promptInput.css('height', ($promptInput[0].scrollHeight) + "px");
	});

	$(document).on('click', '.filter_btn', function () {
		$('.filter_btn').removeClass('active');
		$(this).addClass('active');
		filterType = $(this).attr('filter');
		console.log(filterType);
		updatePrompts(promptsData);
	});

	$(document).on('click', '.favbtn', function (e) {
		console.log($(this).attr('id'));
		updateFav(Number($(this).attr('id')));
		e.stopPropagation();
	});

	$('#chat_add_image').click(function () {
		openNewImageDlg();
	});

	$('#selectImageInput').change(function () {
		if (this.files && this.files[0]) {

			for (let i = 0; i < this.files.length; i++) {
				let reader = new FileReader();

				if (this.files[i].type === 'application/pdf') {
					if (prompt_images.length == 0) {
						pdf = this.files[i];
						console.log(this.files[i].name);
						updatePromptPdfs(this.files[i].name);
					}
				} else {
					if (pdf == undefined) {
						// Existing image handling code
						reader.onload = function (e) {
							var img = new Image();
							img.src = e.target.result;
							img.onload = function () {
								var canvas = document.createElement('canvas');
								var ctx = canvas.getContext('2d');
								canvas.height = img.height * 200 / img.width;
								canvas.width = 200;
								ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
								var base64 = canvas.toDataURL('image/png');
								addImagetoChat(base64);
							}
						};
						reader.readAsDataURL(this.files[i]);
					}
				}
			}
			document.getElementById('mainupscale_src') && (document.getElementById('mainupscale_src').style.display = 'none');
		}
	});

	$('#upscale_src').change(function () {
		if (this.files && this.files[0]) {

			for (let i = 0; i < this.files.length; i++) {
				let reader = new FileReader();

				reader.onload = function (e) {
					// console.log(e);
					addImagetoChat(e.target.result);
					// $( "#selectImageInput" ).val( '' );
				};
				console.log(this.files[i]);
				reader.readAsDataURL(this.files[i]);
			}
		}
		document.getElementById('mainupscale_src') && (document.getElementById('mainupscale_src').style.display = 'none');
	});
}

$(document).ready(initChat);


$(document).ready(function () {
	"use strict";
	updateChatButtons()
	$(".chats-container").stop().animate({ scrollTop: $(".chats-container")[0]?.scrollHeight }, 200);
	$("#scrollable_content").stop().animate({ scrollTop: $("#scrollable_content").outerHeight() }, 200);

	$('.chat-list-ul').on('click', 'a', function () {
		const parentLi = $(this).parent();
		parentLi.siblings().removeClass('active');
		parentLi.addClass('active');
	});

	function saveChatNewTitle(chatId, newTitle) {

		var formData = new FormData();
		formData.append('chat_id', chatId);
		formData.append('title', newTitle);

		$.ajax({
			type: "post",
			url: "/dashboard/user/openai/chat/rename-chat",
			data: formData,
			contentType: false,
			processData: false,
		});
		return false;

	}

	function deleteChatItem(chatId, chatTitle) {
		if (confirm(`Are you sure you want to remove ${chatTitle}?`)) {
			var formData = new FormData();
			formData.append('chat_id', chatId);

			$.ajax({
				type: "post",
				url: "/dashboard/user/openai/chat/delete-chat",
				data: formData,
				contentType: false,
				processData: false,
				success: function (data) {
					//Remove chat li
					$("#" + chatId).hide();
					$("#chat_area_to_hide").hide();
				},
				error: function (data) {
					var err = data.responseJSON.errors;
					if (err) {
						$.each(err, function (index, value) {
							toastr.error(value);
						});
					} else {
						toastr.error(data.responseJSON.message);
					}
				},
			});
		}

	}

	$('.chat-list-ul').on('click', '.chat-item-delete', ev => {
		const button = ev.currentTarget;
		const parent = button.closest('li');
		const chatId = parent.getAttribute('id');
		const chatTitle = parent.querySelector('.chat-item-title').innerText;
		deleteChatItem(chatId, chatTitle);
	});

	$('.chat-list-ul').on('click', '.chat-item-update-title', ev => {
		const button = ev.currentTarget;
		const parent = button.closest('.chat-list-item');
		const title = parent.querySelector('.chat-item-title');
		const chatId = parent.getAttribute('id');
		const currentText = title.innerText;

		function setEditMode(mode) {

			if (mode === 'editStart') {
				parent.classList.add('edit-mode');

				title.setAttribute('data-current-text', currentText);
				title.setAttribute('contentEditable', true);
				title.focus();
				window.getSelection().selectAllChildren(title);
			} else if (mode === 'editEnd') {
				parent.classList.remove('edit-mode');

				title.removeAttribute('contentEditable');
				title.removeAttribute('data-current-text');
			}

		}

		function keydownHandler(ev) {
			const { key } = ev;
			const escapePressed = key === 'Escape';
			const enterPressed = key === 'Enter';

			if (!escapePressed && !enterPressed) return;

			ev.preventDefault();

			if (escapePressed) {
				title.innerText = currentText;
			}

			if (enterPressed) {
				saveChatNewTitle(chatId, title.innerText);
			}

			setEditMode('editEnd');
			document.removeEventListener('keydown', keydownHandler);
		}

		// if alreay editting then turn the edit button to a save button
		if (title.hasAttribute('contentEditable')) {
			setEditMode('editEnd');
			document.removeEventListener('keydown', keydownHandler);
			return saveChatNewTitle(chatId, title.innerText);
		}

		$('.chat-list-ul .edit-mode').each((i, el) => {
			const title = el.querySelector('.chat-item-title');
			title.innerText = title.getAttribute('data-current-text');
			title.removeAttribute('data-current-text');
			title.removeAttribute('contentEditable');
			el.classList.remove('edit-mode');
		});

		setEditMode('editStart');

		document.addEventListener('keydown', keydownHandler);
	});

});

/*
	
DO NOT FORGET TO ADD THE CHANGES TO BOTH FUNCTION makeDocumentReadyAgain and the document ready function on the top!!!!
	
 */
function makeDocumentReadyAgain() {
	updateChatButtons()
	$(document).ready(function () {
		"use strict";

		$(".chats-container").stop().animate({ scrollTop: $(".chats-container")[0]?.scrollHeight }, 200);
		$("#scrollable_content").stop().animate({ scrollTop: $("#scrollable_content").outerHeight() }, 200);

		$('.chat-list-ul').on('click', 'a', function () {
			const parentLi = $(this).parent();
			parentLi.siblings().removeClass('active');
			parentLi.addClass('active');
		});

		function saveChatNewTitle(chatId, newTitle) {

			var formData = new FormData();
			formData.append('chat_id', chatId);
			formData.append('title', newTitle);

			$.ajax({
				type: "post",
				url: "/dashboard/user/openai/chat/rename-chat",
				data: formData,
				contentType: false,
				processData: false,
			});
			return false;

		}

		function deleteChatItem(chatId, chatTitle) {
			if (confirm(`Are you sure you want to remove ${chatTitle}?`)) {
				var formData = new FormData();
				formData.append('chat_id', chatId);

				$.ajax({
					type: "post",
					url: "/dashboard/user/openai/chat/delete-chat",
					data: formData,
					contentType: false,
					processData: false,
					success: function (data) {
						//Remove chat li
						$("#" + chatId).hide();
						$("#chat_area_to_hide").hide();
					},
					error: function (data) {
						var err = data.responseJSON.errors;
						if (err) {
							$.each(err, function (index, value) {
								toastr.error(value);
							});
						} else {
							toastr.error(data.responseJSON.message);
						}
					},
				});
				return false;
			}

		}

		$('.chat-list-ul').off('click', '.chat-item-delete');
		$('.chat-list-ul').on('click', '.chat-item-delete', ev => {
			const button = ev.currentTarget;
			const parent = button.closest('li');
			const chatId = parent.getAttribute('id');
			const chatTitle = parent.querySelector('.chat-item-title').innerText;
			deleteChatItem(chatId, chatTitle);
		});

		$('.chat-list-ul').off('click', '.chat-item-update-title');
		$('.chat-list-ul').on('click', '.chat-item-update-title', ev => {
			const button = ev.currentTarget;
			const parent = button.closest('.chat-list-item');
			const title = parent.querySelector('.chat-item-title');
			const chatId = parent.getAttribute('id');
			const currentText = title.innerText;

			function setEditMode(mode) {

				if (mode === 'editStart') {
					parent.classList.add('edit-mode');

					title.setAttribute('data-current-text', currentText);
					title.setAttribute('contentEditable', true);
					title.focus();
					window.getSelection().selectAllChildren(title);
				} else if (mode === 'editEnd') {
					parent.classList.remove('edit-mode');

					title.removeAttribute('contentEditable');
					title.removeAttribute('data-current-text');
				}

			}

			function keydownHandler(ev) {
				const { key } = ev;
				const escapePressed = key === 'Escape';
				const enterPressed = key === 'Enter';

				if (!escapePressed && !enterPressed) return;

				ev.preventDefault();

				if (escapePressed) {
					title.innerText = currentText;
				}

				if (enterPressed) {
					saveChatNewTitle(chatId, title.innerText);
				}

				setEditMode('editEnd');
				document.removeEventListener('keydown', keydownHandler);
			}

			// if alreay editting then turn the edit button to a save button
			if (title.hasAttribute('contentEditable')) {
				setEditMode('editEnd');
				document.removeEventListener('keydown', keydownHandler);
				return saveChatNewTitle(chatId, title.innerText);
			}

			$('.chat-list-ul .edit-mode').each((i, el) => {
				const title = el.querySelector('.chat-item-title');
				title.innerText = title.getAttribute('data-current-text');
				title.removeAttribute('data-current-text');
				title.removeAttribute('contentEditable');
				el.classList.remove('edit-mode');
			});

			setEditMode('editStart');

			document.addEventListener('keydown', keydownHandler);
		});

	});
}


function escapeHtml(html) {
	var text = document.createTextNode(html);
	var div = document.createElement('div');
	div.appendChild(text);
	return div.innerHTML;
}


function updateChatButtons() {
	setTimeout(function () {
		const generateBtn = document.getElementById("send_message_button");
		const stopBtn = document.getElementById("stop_button");
		const promptInput = document.getElementById("prompt");
		let controller = null; // Store the AbortController instance
		let scrollLocked = false;
		let nIntervId = null;
		let chunk = [];
		let streaming = true;

		const generate = async (ev) => {
			"use strict";
			ev?.preventDefault();
			document.getElementById('mainupscale_src') && (document.getElementById('mainupscale_src').style.display = 'none');
			document.getElementById('sugg') && (document.getElementById('sugg').style.display = 'none');

			// Alert the user if no prompt value
			const promptInputValue = promptInput.value;
			if (!promptInputValue || promptInputValue.length === 0 || promptInputValue.replace(/\s/g, '') === '') {
				return toastr.error('Please fill the message field');
			}

			const chatsContainer = $(".chats-container");

			const userBubbleTemplate = document.querySelector('#chat_user_bubble').content.cloneNode(true);
			const aiBubbleTemplate = document.querySelector('#chat_ai_bubble').content.cloneNode(true);

			if (generateBtn.classList.contains('submitting')) return;

			const prompt1 = atob(guest_event_id);
			const prompt2 = atob(guest_look_id);
			const prompt3 = atob(guest_product_id);

			const chat_id = $('#chat_id').val();

			const bearer = prompt1 + prompt2 + prompt3;
			// Disable the generate button and enable the stop button
			generateBtn.disabled = true;
			$(generateBtn).addClass('hidden');
			$(stopBtn).removeClass('hidden');
			generateBtn.classList.add('submitting');
			stopBtn.disabled = false;
			userBubbleTemplate.querySelector('.chat-content').innerHTML = promptInputValue;
			promptInput.value = '';
			promptInput.style.height = '';
			chatsContainer.append(userBubbleTemplate);
			for (let i = 0; i < prompt_images.length; i++) {
				const chatImageBubbleTemplate = document.querySelector('#chat_user_image_bubble').content.cloneNode(true);
				chatImageBubbleTemplate.querySelector('.img-content').src = prompt_images[i];
				chatsContainer.append(chatImageBubbleTemplate);
			}
			if(pdf != undefined) {
				const chatPdfBubbleTemplate = document.querySelector('#chat_pdf').content.cloneNode(true);
				var blob = new Blob([pdf], { type: 'application/pdf' });
				var url = URL.createObjectURL(blob);
				console.log(pdf.name, url);
				chatPdfBubbleTemplate.querySelector('.pdfname').innerHTML = pdf.name;
				$(chatPdfBubbleTemplate.querySelector('.pdfpath')).attr('href', url);
				chatsContainer.append(chatPdfBubbleTemplate);
			}

			// Create a new AbortController instance
			controller = new AbortController();
			const signal = controller.signal;

			let responseText = '';

			const aiBubbleWrapper = aiBubbleTemplate.firstElementChild;
			aiBubbleWrapper.classList.add('loading');
			aiBubbleTemplate.querySelector('.chat-content').innerHTML = responseText;
			chatsContainer.append(aiBubbleTemplate);

			chatsContainer[0].scrollTo(0, chatsContainer[0].scrollHeight);

			if (prompt_images.length == 0) {
				messages.push({
					role: "user",
					content: promptInputValue
				});
			} else {
				messages.push({
					role: "user",
					content: promptInputValue
				});
				// messages.push({
				// 	role: "user",
				// 	content: [
				// 		{
				// 			type: 'text',
				// 			text: promptInputValue
				// 		},
				// 		...prompt_images.map(item => ({
				// 			type: 'image_url',
				// 			image_url: {
				// 				url: item
				// 			}
				// 		}))
				// 	]
				// });
			}

			let guest_id2 = atob(guest_id);

			function onBeforePageUnload(e) {
				e.preventDefault();
				e.returnValue = '';
			}

			function onWindowScroll() {
				if (chatsContainer[0].scrollTop + chatsContainer[0].offsetHeight >= chatsContainer[0].scrollHeight) {
					scrollLocked = true;
				} else {
					scrollLocked = false;
				}
			}

			// to prevent from reloading when generating respond
			window.addEventListener('beforeunload', onBeforePageUnload);

			chatsContainer[0].addEventListener('scroll', onWindowScroll);

			// started eventSource
			const prompt = document.getElementById("prompt").value;

			chunk = [];
			streaming = true;
			nIntervId = setInterval(function () {
				if (chunk.length == 0 && !streaming) {
					messages.push({
						role: "assistant",
						content: aiBubbleWrapper.querySelector('.chat-content').innerHTML
					});

					if (messages.length >= 6) {
						messages.splice(1, 2);
					}

					saveResponse(promptInputValue, aiBubbleWrapper.querySelector('.chat-content').innerHTML, chat_id, imagePath, pdfName, pdfPath)

					generateBtn.disabled = false;
					generateBtn.classList.remove('submitting');
					aiBubbleWrapper.classList.remove('loading');
					stopBtn.disabled = true;

					$(generateBtn).removeClass('hidden');
					$(stopBtn).addClass('hidden');

					controller = null; // Reset the AbortController instance

					jQuery(".chats-container").stop().animate({ scrollTop: jQuery(".chats-container")[0]?.scrollHeight }, 200);
					jQuery("#scrollable_content").stop().animate({ scrollTop: jQuery("#scrollable_content").outerHeight() }, 200);

					window.removeEventListener('beforeunload', onBeforePageUnload);
					chatsContainer[0].removeEventListener('scroll', onWindowScroll);
					clearInterval(nIntervId);
				}

				const text = chunk.shift();
				if (text) {
					aiBubbleWrapper.classList.remove('loading');
					aiBubbleWrapper.querySelector('.chat-content').innerHTML += text;
					chatsContainer[0].scrollTo(0, chatsContainer[0].scrollHeight);
				}

			}, 20);

			if (stream_type == 'backend') {

				function implementChat(type, images) {
					const eventSource = new EventSource(`${streamUrl}/?message=${promptInputValue}&category=${category.id}&chat_id=${chat_id}&type=${type}&images=${JSON.stringify(images)}`);


					eventSource.addEventListener('data', function (event) {
						const data = JSON.parse(event.data);
						if (data.message !== null)
							chunk.push(data.message);
					});

					// finished eventSource
					eventSource.addEventListener('stop', function (event) {

						$(generateBtn).removeClass('hidden');
						$(stopBtn).addClass('hidden');

						streaming = false;
						eventSource.close();
					});

					// error handler for eventSource
					eventSource.addEventListener('error', (event) => {
						console.log(event);
						aiBubbleWrapper.querySelector('.chat-content').innerHTML = "Api Connection Error."
						eventSource.close()
						clearInterval(nIntervId)
						generateBtn.disabled = false;
						generateBtn.classList.remove('submitting');
						aiBubbleWrapper.classList.remove('loading');
						document.getElementById("chat_form").reset();
						streaming = false
						messages.pop()

						$(generateBtn).removeClass('hidden');
						$(stopBtn).addClass('hidden');
					})
				}
				imagePath = [];
				if (pdf == undefined) {
					pdfName = "";
					pdfPath = "";
					if (prompt_images.length == 0) {
						implementChat('chat');
					} else {
						let temp = [...prompt_images];
						prompt_images = [];
						updatePromptImages();
						$.ajax({
							type: "POST",
							url: '/images/upload',
							data: {
								images: temp
							},
							success: function (result) {
								imagePath = result.path;
								implementChat('vision', result.path);
							}
						});
					}
				} else {
					var formData = new FormData();
					let t = pdf;
					formData.append('pdf', t);
					formData.append('chat_id', chat_id);
					pdf = undefined;
					updatePromptPdfs();
					$.ajax({
						url: '/pdf/upload',
						type: 'POST',
						data: formData,
						processData: false,
						contentType: false,
						success: function (response) {
							pdfPath = response.filename;
							pdfName = t.name;
							pdf = undefined;
							implementChat('chat');
						},
						error: function (xhr, status, error) {
							console.error('Error uploading PDF: ' + error);
						}
					});
				}
			} else {
				try {
					imagePath = [];
					var temp = [...prompt_images];
					async function implementChat() {
						// console.log(messages);
						// Fetch the response from the OpenAI API with the signal from AbortController
						let resmodel = temp.length == 0 ? openai_model : 'gpt-4-vision-preview';
						let resmessages = [...(messages.slice(0, messages.length - 1)), ...training, messages[messages.length - 1]];
						if (resmodel == 'gpt-4-vision-preview') {
							resmessages = [
								{
									role: "user",
									content: [
										{
											type: 'text',
											text: promptInputValue
										},
										...temp.map(item => ({
											type: 'image_url',
											image_url: {
												url: item
											}
										}))
									]
								}];
						}

						var formData = new FormData();
						formData.append('chat_id', chat_id);
						formData.append('prompt', promptInputValue);

						$.ajax({
							url: '/pdf/getContent',
							type: 'POST',
							data: formData,
							processData: false,
							contentType: false,
							success: async function (response_) {
								console.log(response_);
								if (!response_.extra_prompt == "") {
									resmessages = [...(messages.slice(0, messages.length - 1)), ...training, { role: "user", content: "'this pdf' means pdf content. Must not reference previous chats if user asking about pdf. Must reference pdf content if only user is asking about pdf. Else just response as an assistant shortly and professionaly without must not referencing pdf content. \n\n\n\nUser question: " + messages[messages.length - 1].content + "\n\n\n\n\n PDF Content: \n " + response_.extra_prompt }]
								}
								const response = await fetch(guest_id2, {
									method: "POST",
									headers: {
										"Content-Type": "application/json",
										Authorization: `Bearer ${bearer}`,
									},
									body: JSON.stringify({
										model: resmodel,
										messages: resmessages,
										max_tokens: 2000,
										stream: true, // For streaming responses
									}),
									signal, // Pass the signal to the fetch request
								});

								if (response.status != 200) {
									throw response;
								}
								// Read the response as a stream of data
								const reader = response.body.getReader();
								const decoder = new TextDecoder("utf-8");
								let result = '';

								while (true) {
									// if ( window.console || window.console.firebug ) {
									// 	console.clear();
									// }
									const { done, value } = await reader.read();
									if (done) {

										$(generateBtn).removeClass('hidden');
										$(stopBtn).addClass('hidden');

										streaming = false;
										break;
									}
									// Massage and parse the chunk of data
									const chunk1 = decoder.decode(value);
									const lines = chunk1.split("\n");

									const parsedLines = lines
										.map((line) => line.replace(/^data: /, "").trim()) // Remove the "data: " prefix
										.filter((line) => line !== "" && line !== "[DONE]") // Remove empty lines and "[DONE]"
										.map((line) => {
											try {
												return JSON.parse(line);
											} catch (ex) {
												console.log(line);
											}
											return null;
										}); // Parse the JSON string

									for (const parsedLine of parsedLines) {
										if (!parsedLine) continue;
										const { choices } = parsedLine;
										const { delta } = choices[0];
										const { content } = delta;
										// const { finish_reason } = choices[0];

										if (content) {
											chunk.push(content);
										}
									}
								}
							},
							error: function (xhr, status, error) {
								console.error('Error uploading PDF: ' + error);
							}
						});


					}
					if (pdf == undefined) {
						prompt_images = [];
						updatePromptImages();
						$.ajax({
							type: "POST",
							url: '/images/upload',
							data: {
								images: temp
							},
							success: function (result) {
								imagePath = result.path;
								implementChat();
							}
						});
					} else {
						var formData = new FormData();
						let t = pdf;
						formData.append('pdf', t);
						formData.append('chat_id', chat_id);
						pdf = undefined;
						updatePromptPdfs();
						$.ajax({
							url: '/pdf/upload',
							type: 'POST',
							data: formData,
							processData: false,
							contentType: false,
							success: function (response) {
								pdfPath = response.filename;
								pdfName = t.name;
								implementChat();
								pdf = undefined;
							},
							error: function (xhr, status, error) {
								console.error('Error uploading PDF: ' + error);
							}
						});
					}


				} catch (error) {

					$(generateBtn).removeClass('hidden');
					$(stopBtn).addClass('hidden');

					// Handle fetch request errors
					if (signal.aborted) {
						aiBubbleWrapper.querySelector('.chat-content').innerHTML = "Request aborted by user. Not saved.";
					} else {
						switch (error.status) {
							case 429:
								aiBubbleWrapper.querySelector('.chat-content').innerHTML = "Api Connection Error. You hit the rate limites of openai requests. Please check your Openai API Key.";
								break;
							default:
								aiBubbleWrapper.querySelector('.chat-content').innerHTML = "Api Connection Error. Please contact system administrator via Support Ticket. Error is: API Connection failed due to API keys.";
						}

					}
					clearInterval(nIntervId)
					generateBtn.disabled = false;
					generateBtn.classList.remove('submitting');
					aiBubbleWrapper.classList.remove('loading');
					document.getElementById("chat_form").reset();
					streaming = false
					messages.pop()
				}
			}

		};

		const stop = () => {
			// Abort the fetch request by calling abort() on the AbortController instance

			$(generateBtn).removeClass('hidden');
			$(stopBtn).addClass('hidden');

			if (controller) {
				controller.abort();
				controller = null;
				chunk = [];
				streaming = false;
			}
		};
		promptInput.addEventListener('keypress', ev => {
			if (ev.keyCode == 13) {
				ev.preventDefault();
				return generate();
			}
		});

		generateBtn.addEventListener("click", generate);
		stopBtn.addEventListener("click", stop);

	}, 100);

}


function openChatAreaContainer(chat_id) {
	"use strict";

	chatid = chat_id;

	var formData = new FormData();
	formData.append('chat_id', chat_id);

	$.ajax({
		type: "post",
		url: "/dashboard/user/openai/chat/open-chat-area-container",
		data: formData,
		contentType: false,
		processData: false,
		success: function (data) {
			$("#load_chat_area_container").html(data.html);
			initChat();
			// $( "#show_export_btns" ).on( 'mouseenter', function ( e ) {
			// 	$( "#export_btns" ).removeClass( 'hidden' );
			// } );

			// $( "#show_export_btns" ).on( 'mouseleave', function ( e ) {
			// 	$( "#export_btns" ).addClass( 'hidden' );
			// } );

			messages = [{
				role: "assistant",
				content: prompt_prefix
			}]

			data.lastThreeMessage.forEach(message => {
				messages.push({
					role: "user",
					content: message.input
				});
				messages.push({
					role: "assistant",
					content: message.output
				});
			});

			makeDocumentReadyAgain();
			if (data.lastThreeMessage != "") {
				document.getElementById('mainupscale_src') && (document.getElementById('mainupscale_src').style.display = 'none');
				document.getElementById('sugg') && (document.getElementById('sugg').style.display = 'none');
			}
			setTimeout(function () {
				$(".chats-container").stop().animate({ scrollTop: $(".chats-container")[0].scrollHeight }, 200);
			}, 750);
		},
		error: function (data) {
			var err = data.responseJSON.errors;
			if (err) {
				$.each(err, function (index, value) {
					toastr.error(value);
				});
			} else {
				toastr.error(data.responseJSON.message);
			}
		},
	});

	return false;
}

function startNewChat(category_id, local) {
	"use strict";
	var formData = new FormData();
	formData.append('category_id', category_id);

	$.ajax({
		type: "post",
		url: "/" + local + "/dashboard/user/openai/chat/start-new-chat",
		data: formData,
		contentType: false,
		processData: false,
		success: function (data) {
			chatid = data.id;
			$("#load_chat_area_container").html(data.html);
			$("#chat_sidebar_container").html(data.html2);
			initChat();
			// $( "#show_export_btns" ).on( 'mouseenter', function ( e ) {
			// 	$( "#export_btns" ).removeClass( 'hidden' );
			// } );

			// $( "#show_export_btns" ).on( 'mouseleave', function ( e ) {
			// 	$( "#export_btns" ).addClass( 'hidden' );
			// } );
			messages = [{
				role: "assistant",
				content: prompt_prefix
			}]
			makeDocumentReadyAgain();
			setTimeout(function () {
				$(".chats-container").stop().animate({ scrollTop: $(".chats-container").outerHeight() }, 200);
			}, 750);

		},
		error: function (data) {
			var err = data.responseJSON.errors;
			if (err) {
				$.each(err, function (index, value) {
					toastr.error(value);
				});
			} else {
				toastr.error(data.responseJSON.message);
			}
		},
	});
	return false;
}



$(document).ready(function () {
	$("#chat_search_word").on('keyup', function () {
		return searchChatFunction();
	});

	$('#prompt').on('input', ev => {
		const el = ev.target;
		el.style.height = "5px";
		el.style.height = (el.scrollHeight) + "px";
	})
});


function searchChatFunction() {
	"use strict";

	const categoryId = $('#chat_search_word').data("category-id");
	const formData = new FormData();
	formData.append('_token', document.querySelector("input[name=_token]")?.value);
	formData.append('search_word', document.getElementById('chat_search_word').value);
	formData.append('category_id', categoryId);

	$.ajax({
		type: "POST",
		url: '/dashboard/user/openai/chat/search',
		data: formData,
		contentType: false,
		processData: false,
		success: function (result) {
			$("#chat_sidebar_container").html(result.html);
			$(document).trigger('ready');
		}
	});
}


