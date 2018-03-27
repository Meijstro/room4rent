<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Nahid\Talk\Facades\Talk;
use Auth;
use View;

class MessageController extends Controller
{
    protected $authUser;

    public function __construct()
    {
      // $this->middleware('talkUser');

      View::composer('messages.peoplelist', function($view) {
        $threads = Talk::threads();
        $view->with(compact('threads'));
      });
    }

    public function index()
    {
      if(Auth::user()->role === 'Administrator') {
        $users = User::all();
        return view('messages.index', compact('users'));
      } else {
        return redirect('/dashboard');
      }
    }

    public function chatHistory($id)
    {
    $conversations = Talk::getMessagesByUserId($id);
    $user = '';
    $messages = [];
    if(!$conversations) {
        $user = User::find($id);
    } else {
        $user = $conversations->withUser;
        $messages = $conversations->messages;
    }
    $convo = Talk::isConversationExists($id);

    return view('messages.conversations', compact('messages', 'user', 'convo'));
    }

    public function ajaxSendMessage(Request $request)
    {
      if ($request->ajax()) {
          $rules = [
              'message-data'=>'required',
              '_id'=>'required'
          ];
          $this->validate($request, $rules);
          $body = $request->input('message-data');
          $userId = $request->input('_id');

          if ($message = Talk::sendMessageByUserId($userId, $body)) {

              $html = view('ajax.newMessageHTML', compact('message'))->render();
              return response()->json(['status'=>'success', 'html'=>$html], 200);
          }
      }
    }

    public function counter($id, Request $request)
    {
      $data['current'] = count(Talk::getConversationsById($id)->messages);
      $data['update'] = false;

      $counter = $request->input('counter');

      if (isset($counter) && !empty($counter) && $counter!=$data['current'] )
      {
        $conversations = Talk::getConversationsById($id);
        $data['userID'] = $conversations->messages->last()->user_id;
        $data['sender'] = $conversations->messages->last()->sender->name;
        $data['date'] = $conversations->messages->last()->humans_time;
        $data['message'] = $conversations->messages->last()->message;
        $data['update'] = true;
       }

      return json_encode($data);
      }

    public function test()
    {
      $test = Auth::user()->role;
      dd($test);
    }
}