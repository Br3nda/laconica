// add a notice encoded as JSON into the current timeline
//

RealtimeUpdate = {

     _userid: 0,
     _replyurl: '',
     _favorurl: '',
     _deleteurl: '',

     init: function(userid, replyurl, favorurl, deleteurl)
     {
          RealtimeUpdate._userid = userid;
          RealtimeUpdate._replyurl = replyurl;
          RealtimeUpdate._favorurl = favorurl;
          RealtimeUpdate._deleteurl = deleteurl;
     },

     receive: function(data)
     {
          id = data.id;

          // Don't add it if it already exists

          if ($("#notice-"+id).length > 0) {
               return;
          }

          var noticeItem = RealtimeUpdate.makeNoticeItem(data);
          $("#notices_primary .notices").prepend(noticeItem, true);
          $("#notices_primary .notice:first").css({display:"none"});
          $("#notices_primary .notice:first").fadeIn(1000);
          NoticeReply();
     },

     makeNoticeItem: function(data)
     {
          user = data['user'];
          html = data['html'].replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"');
          source = data['source'].replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"');

          ni = "<li class=\"hentry notice\" id=\"notice-"+data['id']+"\">"+
               "<div class=\"entry-title\">"+
               "<span class=\"vcard author\">"+
               "<a href=\""+user['profile_url']+"\" class=\"url\">"+
               "<img src=\""+user['profile_image_url']+"\" class=\"avatar photo\" width=\"48\" height=\"48\" alt=\""+user['screen_name']+"\"/>"+
               "<span class=\"nickname fn\">"+user['screen_name']+"</span>"+
               "</a>"+
               "</span>"+
               "<p class=\"entry-content\">"+html+"</p>"+
               "</div>"+
               "<div class=\"entry-content\">"+
               "<dl class=\"timestamp\">"+
               "<dt>Published</dt>"+
               "<dd>"+
               "<a rel=\"bookmark\" href=\""+data['url']+"\" >"+
               "<abbr class=\"published\" title=\""+data['created_at']+"\">a few seconds ago</abbr>"+
               "</a> "+
               "</dd>"+
               "</dl>"+
               "<dl class=\"device\">"+
               "<dt>From</dt> "+
               "<dd>"+source+"</dd>"+ // may have a link, I think
               "</dl>";

          if (data['in_reply_to_status_id']) {
               ni = ni+" <dl class=\"response\">"+
                    "<dt>To</dt>"+
                    "<dd>"+
                    "<a href=\""+data['in_reply_to_status_url']+"\" rel=\"in-reply-to\">in reply to</a>"+
                    "</dd>"+
                    "</dl>";
          }

          ni = ni+"</div>"+
               "<div class=\"notice-options\">";

          if (RealtimeUpdate._userid != 0) {
               var input = $("form#form_notice fieldset input#token");
               var session_key = input.val();
               ni = ni+RealtimeUpdate.makeFavoriteForm(data['id'], session_key);
               ni = ni+RealtimeUpdate.makeReplyLink(data['id'], data['user']['screen_name']);
               if (RealtimeUpdate._userid == data['user']['id']) {
                    ni = ni+RealtimeUpdate.makeDeleteLink(data['id']);
               }
          }

          ni = ni+"</div>"+
               "</li>";
          return ni;
     },

     makeFavoriteForm: function(id, session_key)
     {
          var ff;

          ff = "<form id=\"favor-"+id+"\" class=\"form_favor\" method=\"post\" action=\""+RealtimeUpdate._favorurl+"\">"+
               "<fieldset>"+
               "<legend>Favor this notice</legend>"+ // XXX: i18n
               "<input name=\"token-"+id+"\" type=\"hidden\" id=\"token-"+id+"\" value=\""+session_key+"\"/>"+
               "<input name=\"notice\" type=\"hidden\" id=\"notice-n"+id+"\" value=\""+id+"\"/>"+
               "<input type=\"submit\" id=\"favor-submit-"+id+"\" name=\"favor-submit-"+id+"\" class=\"submit\" value=\"Favor\" title=\"Favor this notice\"/>"+
               "</fieldset>"+
               "</form>";
          return ff;
     },

     makeReplyLink: function(id, nickname)
     {
          var rl;
          rl = "<dl class=\"notice_reply\">"+
               "<dt>Reply to this notice</dt>"+
               "<dd>"+
               "<a href=\""+RealtimeUpdate._replyurl+"?replyto="+nickname+"\" title=\"Reply to this notice\">Reply <span class=\"notice_id\">"+id+"</span>"+
               "</a>"+
               "</dd>"+
               "</dl>";
          return rl;
     },

     makeDeleteLink: function(id)
     {
          var dl, delurl;
          delurl = RealtimeUpdate._deleteurl.replace("0000000000", id);

          dl = "<dl class=\"notice_delete\">"+
               "<dt>Delete this notice</dt>"+
               "<dd>"+
               "<a href=\""+delurl+"\" title=\"Delete this notice\">Delete</a>"+
               "</dd>"+
               "</dl>";

          return dl;
     },
}
