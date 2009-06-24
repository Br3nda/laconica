/* local and remote users have profiles */

create sequence profile_seq;
create table profile (
    id bigint default nextval('profile_seq') primary key /* comment 'unique identifier' */,
    nickname varchar(64) not null /* comment 'nickname or username' */,
    fullname varchar(255) /* comment 'display name' */,
    profileurl varchar(255) /* comment 'URL, cached so we dont regenerate' */,
    homepage varchar(255) /* comment 'identifying URL' */,
    bio varchar(140) /* comment 'descriptive biography' */,
    location varchar(255) /* comment 'physical location' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,

    textsearch tsvector
);
create index profile_nickname_idx on profile using btree(nickname);

create table avatar (
    profile_id integer not null /* comment 'foreign key to profile table' */ references profile (id) ,
    original integer default 0 /* comment 'uploaded by user or generated?' */,
    width integer not null /* comment 'image width' */,
    height integer not null /* comment 'image height' */,
    mediatype varchar(32) not null /* comment 'file type' */,
    filename varchar(255) null /* comment 'local filename, if local' */,
    url varchar(255) unique /* comment 'avatar location' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,

    primary key(profile_id, width, height)
);
create index avatar_profile_id_idx on avatar using btree(profile_id);

create sequence sms_carrier_seq;
create table sms_carrier (
    id bigint default nextval('sms_carrier_seq') primary key /* comment 'primary key for SMS carrier' */,
    name varchar(64) unique /* comment 'name of the carrier' */,
    email_pattern varchar(255) not null /* comment 'sprintf pattern for making an email address from a phone number' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified ' */
);

/* local users */

create table "user" (
    id integer primary key /* comment 'foreign key to profile table' */ references profile (id) ,
    nickname varchar(64) unique /* comment 'nickname or username, duped in profile' */,
    password varchar(255) /* comment 'salted password, can be null for OpenID users' */,
    email varchar(255) unique /* comment 'email address for password recovery etc.' */,
    incomingemail varchar(255) unique /* comment 'email address for post-by-email' */,
    emailnotifysub integer default 1 /* comment 'Notify by email of subscriptions' */,
    emailnotifyfav integer default 1 /* comment 'Notify by email of favorites' */,
    emailnotifynudge integer default 1 /* comment 'Notify by email of nudges' */,
    emailnotifymsg integer default 1 /* comment 'Notify by email of direct messages' */,
    emailnotifyattn integer default 1 /* command 'Notify by email of @-replies' */, 
    emailmicroid integer default 1 /* comment 'whether to publish email microid' */,
    language varchar(50) /* comment 'preferred language' */,
    timezone varchar(50) /* comment 'timezone' */,
    emailpost integer default 1 /* comment 'Post by email' */,
    jabber varchar(255) unique /* comment 'jabber ID for notices' */,
    jabbernotify integer default 0 /* comment 'whether to send notices to jabber' */,
    jabberreplies integer default 0 /* comment 'whether to send notices to jabber on replies' */,
    jabbermicroid integer default 1 /* comment 'whether to publish xmpp microid' */,
    updatefrompresence integer default 0 /* comment 'whether to record updates from Jabber presence notices' */,
    sms varchar(64) unique /* comment 'sms phone number' */,
    carrier integer /* comment 'foreign key to sms_carrier' */ references sms_carrier (id) ,
    smsnotify integer default 0 /* comment 'whether to send notices to SMS' */,
    smsreplies integer default 0 /* comment 'whether to send notices to SMS on replies' */,
    smsemail varchar(255) /* comment 'built from sms and carrier' */,
    uri varchar(255) unique /* comment 'universally unique identifier, usually a tag URI' */,
    autosubscribe integer default 0 /* comment 'automatically subscribe to users who subscribe to us' */,
    urlshorteningservice varchar(50) default 'ur1.ca' /* comment 'service to use for auto-shortening URLs' */,
    inboxed integer default 0 /* comment 'has an inbox been created for this user?' */, 
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */

);
create index user_smsemail_idx on "user" using btree(smsemail);

/* remote people */

create table remote_profile (
    id integer primary key /* comment 'foreign key to profile table' */ references profile (id) ,
    uri varchar(255) unique /* comment 'universally unique identifier, usually a tag URI' */,
    postnoticeurl varchar(255) /* comment 'URL we use for posting notices' */,
    updateprofileurl varchar(255) /* comment 'URL we use for updates to this profile' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */
);

create table subscription (
    subscriber integer not null /* comment 'profile listening' */,
    subscribed integer not null /* comment 'profile being listened to' */,
    jabber integer default 1 /* comment 'deliver jabber messages' */,
    sms integer default 1 /* comment 'deliver sms messages' */,
    token varchar(255) /* comment 'authorization token' */,
    secret varchar(255) /* comment 'token secret' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,

    primary key (subscriber, subscribed)
);
create index subscription_subscriber_idx on subscription using btree(subscriber);
create index subscription_subscribed_idx on subscription using btree(subscribed);

create sequence notice_seq;
create table notice (

    id bigint default nextval('notice_seq') primary key /* comment 'unique identifier' */,
    profile_id integer not null /* comment 'who made the update' */ references profile (id) ,
    uri varchar(255) unique /* comment 'universally unique identifier, usually a tag URI' */,
    content varchar(140) /* comment 'update content' */,
    rendered text /* comment 'HTML version of the content' */,
    url varchar(255) /* comment 'URL of any attachment (image, video, bookmark, whatever)' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,
    reply_to integer /* comment 'notice replied to (usually a guess)' */ references notice (id) ,
    is_local integer default 0 /* comment 'notice was generated by a user' */,
    source varchar(32) /* comment 'source of comment, like "web", "im", or "clientname"' */,
    conversation integer /*id of root notice in this conversation' */ references notice (id)


/*    FULLTEXT(content) */
);
create index notice_profile_id_idx on notice using btree(profile_id);
create index notice_created_idx on notice using btree(created);

create table notice_source (
     code varchar(32) primary key not null /* comment 'source code' */,
     name varchar(255) not null /* comment 'name of the source' */,
     url varchar(255) not null /* comment 'url to link to' */,
     created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
     modified timestamp /* comment 'date this record was modified' */
);

create table reply (

    notice_id integer not null /* comment 'notice that is the reply' */ references notice (id) ,
    profile_id integer not null /* comment 'profile replied to' */ references profile (id) ,
    modified timestamp /* comment 'date this record was modified' */,
    replied_id integer /* comment 'notice replied to (not used, see notice.reply_to)' */,

    primary key (notice_id, profile_id)

);
create index reply_notice_id_idx on reply using btree(notice_id);
create index reply_profile_id_idx on reply using btree(profile_id);
create index reply_replied_id_idx on reply using btree(replied_id);

create table fave (

    notice_id integer not null /* comment 'notice that is the favorite' */ references notice (id),
    user_id integer not null /* comment 'user who likes this notice' */ references "user" (id) ,
    modified timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was modified' */,
    primary key (notice_id, user_id)

);
create index fave_notice_id_idx on fave using btree(notice_id);
create index fave_user_id_idx on fave using btree(user_id);
create index fave_modified_idx on fave using btree(modified);

/* tables for OAuth */

create table consumer (
    consumer_key varchar(255) primary key /* comment 'unique identifier, root URL' */,
    seed char(32) not null /* comment 'seed for new tokens by this consumer' */,

    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */
);

create table token (
    consumer_key varchar(255) not null /* comment 'unique identifier, root URL' */ references consumer (consumer_key),
    tok char(32) not null /* comment 'identifying value' */,
    secret char(32) not null /* comment 'secret value' */,
    type integer not null default 0 /* comment 'request or access' */,
    state integer default 0 /* comment 'for requests 0 = initial, 1 = authorized, 2 = used' */,

    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,

    primary key (consumer_key, tok)
);

create table nonce (
    consumer_key varchar(255) not null /* comment 'unique identifier, root URL' */,
    tok char(32) not null /* comment 'identifying value' */,
    nonce char(32) null /* comment 'buggy old value, ignored */,
    ts integer not null /* comment 'timestamp sent' values are epoch, and only used internally */,

    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,

    primary key (consumer_key, ts, nonce)
);

/* One-to-many relationship of user to openid_url */

create table user_openid (
    canonical varchar(255) primary key /* comment 'Canonical true URL' */,
    display varchar(255) not null unique /* comment 'URL for viewing, may be different from canonical' */,
    user_id integer not null /* comment 'user owning this URL' */ references "user" (id) ,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */

);
create index user_openid_user_id_idx on user_openid using btree(user_id);

/* These are used by JanRain OpenID library */

create table oid_associations (
    server_url varchar(2047),
    handle varchar(255),
    secret bytea,
    issued integer,
    lifetime integer,
    assoc_type varchar(64),
    primary key (server_url, handle)
);

create table oid_nonces (
    server_url varchar(2047),
    "timestamp" integer,
    salt character(40),
    unique (server_url, "timestamp", salt)
);

create table confirm_address (
    code varchar(32) not null primary key /* comment 'good random code' */,
    user_id integer not null /* comment 'user who requested confirmation' */ references "user" (id),
    address varchar(255) not null /* comment 'address (email, Jabber, SMS, etc.)' */,
    address_extra varchar(255) not null default '' /* comment 'carrier ID, for SMS' */,
    address_type varchar(8) not null /* comment 'address type ("email", "jabber", "sms")' */,
    claimed timestamp /* comment 'date this was claimed for queueing' */,
    sent timestamp /* comment 'date this was sent for queueing' */,
    modified timestamp /* comment 'date this record was modified' */
);

create table remember_me (
    code varchar(32) not null primary key /* comment 'good random code' */,
    user_id integer not null /* comment 'user who is logged in' */ references "user" (id),
    modified timestamp /* comment 'date this record was modified' */
);

create table queue_item (

    notice_id integer not null /* comment 'notice queued' */ references notice (id) ,
    transport varchar(8) not null /* comment 'queue for what? "email", "jabber", "sms", "irc", ...' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    claimed timestamp /* comment 'date this item was claimed' */,

    primary key (notice_id, transport)

);
create index queue_item_created_idx on queue_item using btree(created);

/* Hash tags */
create table notice_tag (
    tag varchar( 64 ) not null /* comment 'hash tag associated with this notice' */,
    notice_id integer not null /* comment 'notice tagged' */ references notice (id) ,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,

    primary key (tag, notice_id)
);
create index notice_tag_created_idx on notice_tag using btree(created);

/* Synching with foreign services */

create table foreign_service (
     id int not null primary key /* comment 'numeric key for service' */,
     name varchar(32) not null unique /* comment 'name of the service' */,
     description varchar(255) /* comment 'description' */,
     created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
     modified timestamp /* comment 'date this record was modified' */
);

create table foreign_user (
     id int not null unique /* comment 'unique numeric key on foreign service' */,
     service int not null /* comment 'foreign key to service' */ references foreign_service(id) ,
     uri varchar(255) not null unique /* comment 'identifying URI' */,
     nickname varchar(255) /* comment 'nickname on foreign service' */,
     created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
     modified timestamp /* comment 'date this record was modified' */,
     
     primary key (id, service)
);

create table foreign_link (
     user_id int /* comment 'link to user on this system, if exists' */ references "user" (id),
     foreign_id int /* comment 'link' */ references foreign_user (id),
     service int not null /* comment 'foreign key to service' */ references foreign_service (id),
     credentials varchar(255) /* comment 'authc credentials, typically a password' */,
     noticesync int not null default 1 /* comment 'notice synchronisation, bit 1 = sync outgoing, bit 2 = sync incoming, bit 3 = filter local replies' */,
     friendsync int not null default 2 /* comment 'friend synchronisation, bit 1 = sync outgoing, bit 2 = sync incoming */, 
     profilesync int not null default 1 /* comment 'profile synchronization, bit 1 = sync outgoing, bit 2 = sync incoming' */,
     last_noticesync timestamp default null /* comment 'last time notices were imported' */,
     last_friendsync timestamp default null /* comment 'last time friends were imported' */,
     created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
     modified timestamp /* comment 'date this record was modified' */,

     primary key (user_id,foreign_id,service)
);
create index foreign_user_user_id_idx on foreign_link using btree(user_id);

create table foreign_subscription (
     service int not null /* comment 'service where relationship happens' */ references foreign_service(id) ,
     subscriber int not null /* comment 'subscriber on foreign service' */ ,
     subscribed int not null /* comment 'subscribed user' */ ,
     created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
     
     primary key (service, subscriber, subscribed)
);
create index foreign_subscription_subscriber_idx on foreign_subscription using btree(subscriber);
create index foreign_subscription_subscribed_idx on foreign_subscription using btree(subscribed);

create table invitation (
     code varchar(32) not null primary key /* comment 'random code for an invitation' */,
     user_id int not null /* comment 'who sent the invitation' */ references "user" (id),
     address varchar(255) not null /* comment 'invitation sent to' */,
     address_type varchar(8) not null /* comment 'address type ("email", "jabber", "sms") '*/,
     created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */

);
create index invitation_address_idx on invitation using btree(address,address_type);
create index invitation_user_id_idx on invitation using btree(user_id);

create sequence message_seq;
create table message (

    id bigint default nextval('message_seq') primary key /* comment 'unique identifier' */,
    uri varchar(255) unique /* comment 'universally unique identifier' */,
    from_profile integer not null /* comment 'who the message is from' */ references profile (id),
    to_profile integer not null /* comment 'who the message is to' */ references profile (id),
    content varchar(140) /* comment 'message content' */,
    rendered text /* comment 'HTML version of the content' */,
    url varchar(255) /* comment 'URL of any attachment (image, video, bookmark, whatever)' */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,
    source varchar(32) /* comment 'source of comment, like "web", "im", or "clientname"' */
    
);
create index message_from_idx on message using btree(from_profile);
create index message_to_idx on message using btree(to_profile);
create index message_created_idx on message using btree(created);

create table notice_inbox (

    user_id integer not null /* comment 'user receiving the message' */ references "user" (id),
    notice_id integer not null /* comment 'notice received' */ references notice (id),
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date the notice was created' */,
    source integer default 1 /* comment 'reason it is in the inbox: 1=subscription' */,

    primary key (user_id, notice_id)
);
create index notice_inbox_notice_id_idx on notice_inbox using btree(notice_id);

create table profile_tag (
   tagger integer not null /* comment 'user making the tag' */ references "user" (id),
   tagged integer not null /* comment 'profile tagged' */ references profile (id),
   tag varchar(64) not null /* comment 'hash tag associated with this notice' */,
   modified timestamp /* comment 'date the tag was added' */,

   primary key (tagger, tagged, tag)
);
create index profile_tag_modified_idx on profile_tag using btree(modified);
create index profile_tag_tagger_tag_idx on profile_tag using btree(tagger,tag);

create table profile_block (

   blocker integer not null /* comment 'user making the block' */ references "user" (id),
   blocked integer not null /* comment 'profile that is blocked' */ references profile (id),
   modified timestamp /* comment 'date of blocking' */,

   primary key (blocker, blocked)

);

create sequence user_group_seq;
create table user_group (

    id bigint default nextval('user_group_seq') primary key /* comment 'unique identifier' */,

    nickname varchar(64) unique /* comment 'nickname for addressing' */,
    fullname varchar(255) /* comment 'display name' */,
    homepage varchar(255) /* comment 'URL, cached so we dont regenerate' */,
    description varchar(140) /* comment 'descriptive biography' */,
    location varchar(255) /* comment 'related physical location, if any' */,

    original_logo varchar(255) /* comment 'original size logo' */,
    homepage_logo varchar(255) /* comment 'homepage (profile) size logo' */,
    stream_logo varchar(255) /* comment 'stream-sized logo' */,
    mini_logo varchar(255) /* comment 'mini logo' */,

    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */

);
create index user_group_nickname_idx on user_group using btree(nickname);

create table group_member (

    group_id integer not null /* comment 'foreign key to user_group' */ references user_group (id),
    profile_id integer not null /* comment 'foreign key to profile table' */ references profile (id),
    is_admin integer default 0 /* comment 'is this user an admin?' */,

    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,
    modified timestamp /* comment 'date this record was modified' */,

    primary key (group_id, profile_id)
);

create table related_group (

    group_id integer not null /* comment 'foreign key to user_group' */ references user_group (id) ,
    related_group_id integer not null /* comment 'foreign key to user_group' */ references user_group (id),

    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date this record was created' */,

    primary key (group_id, related_group_id)

);

create table group_inbox (
    group_id integer not null /* comment 'group receiving the message' references user_group (id) */,
    notice_id integer not null /* comment 'notice received' references notice (id) */,
    created timestamp not null default CURRENT_TIMESTAMP /* comment 'date the notice was created' */,

    primary key (group_id, notice_id)
);
create index group_inbox_created_idx on group_inbox using btree(created);


/*attachments and URLs stuff */
create sequence file_seq;
create table file (
    id bigint default nextval('file_seq') primary key /* comment 'unique identifier' */,
    url varchar(255) unique, 
    mimetype varchar(50), 
    size integer, 
    title varchar(255), 
    date integer, 
    protected integer
);

create sequence file_oembed_seq;
create table file_oembed (
    id bigint default nextval('file_oembed_seq') primary key /* comment 'unique identifier' */,
    file_id bigint unique,
    version varchar(20),
    type varchar(20),
    provider varchar(50),
    provider_url varchar(255),
    width integer,
    height integer,
    html text,
    title varchar(255),
    author_name varchar(50), 
    author_url varchar(255), 
    url varchar(255) 
);

create sequence file_redirection_seq;
create table file_redirection (
    id bigint default nextval('file_redirection_seq') primary key /* comment 'unique identifier' */,
    url varchar(255) unique, 
    file_id bigint, 
    redirections integer, 
    httpcode integer
);

create sequence file_thumbnail_seq;
create table file_thumbnail (
    id bigint default nextval('file_thumbnail_seq') primary key /* comment 'unique identifier' */,
    file_id bigint unique, 
    url varchar(255) unique, 
    width integer, 
    height integer 
);

create sequence file_to_post_seq;
create table file_to_post (
    id bigint default nextval('file_to_post_seq') primary key /* comment 'unique identifier' */,
    file_id bigint, 
    post_id bigint, 

    unique(file_id, post_id)
);

create sequence design_seq;
create table design (
    id bigint default nextval('design_seq') /* comment 'design ID'*/,
    backgroundcolor integer /* comment 'main background color'*/ ,
    contentcolor integer /*comment 'content area background color'*/ ,
    sidebarcolor integer /*comment 'sidebar background color'*/ ,
    textcolor integer /*comment 'text color'*/ ,
    linkcolor integer /*comment 'link color'*/,
    backgroundimage varchar(255) /*comment 'background image, if any'*/,
    disposition int default 1 /*comment 'bit 1 = hide background image, bit 2 = display background image, bit 4 = tile background image'*/,
    primary key (id)
);

/* Textsearch stuff */

create index textsearch_idx on profile using gist(textsearch);
create index noticecontent_idx on notice using gist(to_tsvector('english',content));
create trigger textsearchupdate before insert or update on profile for each row
execute procedure tsvector_update_trigger(textsearch, 'pg_catalog.english', nickname, fullname, location, bio, homepage);

