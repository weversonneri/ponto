<?php
use \Illuminate\Support\MessageBag;

class UserController extends \BaseController {

	public function __construct()
    {
        $this->beforeFilter('auth');
    }

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		if (!$this->checkPermission(2)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}
		$users = User::paginate(15);
		return View::make('user.index')->with('users', $users);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		if (!$this->checkPermission(2)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}
		return View::make('user.create');
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		if (!$this->checkPermission(2)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}
		$validator = Validator::make(
			Input::all(), 
			Array(
				'name' => 'required',
				'surname' => 'required',
				'email' => 'required|email|unique:users',
				'password' => 'required|confirmed|min:6',
				'level' => 'required|integer|level_check',
				'day_0_time_in' => 'sometimes|required|TimeFormat',
				'day_0_time_out' => 'sometimes|required|TimeFormat',
				'day_1_time_in' => 'sometimes|required|TimeFormat',
				'day_1_time_out' => 'sometimes|required|TimeFormat',
				'day_2_time_in' => 'sometimes|required|TimeFormat',
				'day_2_time_out' => 'sometimes|required|TimeFormat',
				'day_3_time_in' => 'sometimes|required|TimeFormat',
				'day_3_time_out' => 'sometimes|required|TimeFormat',
				'day_4_time_in' => 'sometimes|required|TimeFormat',
				'day_4_time_out' => 'sometimes|required|TimeFormat',
				'day_5_time_in' => 'sometimes|required|TimeFormat',
				'day_5_time_out' => 'sometimes|required|TimeFormat',
				'day_6_time_in' => 'sometimes|required|TimeFormat',
				'day_6_time_out' => 'sometimes|required|TimeFormat'
			)
		);


		if ($validator->fails())
		{
			return Redirect::back()->withInput()->withErrors($validator);
		}

		$user = User::create(
			Array(
				'name' => Input::get('name'),
				'surname' => Input::get('surname'),
				'email' => Input::get('email'),
				'level' => Input::get('level'),
				'password' => Hash::make(Input::get('password'))
			)
		);
		for($n=0;$n<7;$n++){
			if(Input::get("day_check_$n") == 1){
				$weekday = new UsersTimes();
				$weekday->user_id = $user->id;
				$weekday->weekday = $n;
				$weekday->time_in = Input::get("day_".$n."_time_in");
				$weekday->time_out = Input::get("day_".$n."_time_out");
				$weekday->save();
			}
		}

		return Redirect::route('user.index');
	}

	public function report($id)
	{	
		return View::make('user/report')->with("id",$id);
	}
	public function reportDate($id)
	{
		$month = Input::get('month');
		$year = Input::get('year');
		return View::make('user/report')->with(array("id"=>$id, "month"=>$month, "year"=>$year));
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		if (!$this->checkPermission(2, $id)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}

		$user = User::findOrFail($id);
		$response = View::make('user.edit')->with('user', $user);
		foreach(UsersTimes::where('user_id',$id)->get() AS $key){
			$response->with('day_' . $key->weekday . '_time_in', $key->time_in);
			$response->with('day_' . $key->weekday . '_time_out', $key->time_out);
		}
		return $response; 

	}

	public function reports($id)
	{
		return Redirect::to('user/reports');
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		if (!$this->checkPermission(2, $id)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}

		$user = User::findOrFail($id);
		$user_time = UsersTimes::where('user_id',$id);

		$validator = Validator::make(
			Input::all(), 
			Array(
				'name' => 'required',
				'surname' => 'required',
				'password' => 'confirmed|min:6',
				'level' => 'required|integer|level_check',
				'day_0_time_in' => 'sometimes|required|TimeFormat',
				'day_0_time_out' => 'sometimes|required|TimeFormat',
				'day_1_time_in' => 'sometimes|required|TimeFormat',
				'day_1_time_out' => 'sometimes|required|TimeFormat',
				'day_2_time_in' => 'sometimes|required|TimeFormat',
				'day_2_time_out' => 'sometimes|required|TimeFormat',
				'day_3_time_in' => 'sometimes|required|TimeFormat',
				'day_3_time_out' => 'sometimes|required|TimeFormat',
				'day_4_time_in' => 'sometimes|required|TimeFormat',
				'day_4_time_out' => 'sometimes|required|TimeFormat',
				'day_5_time_in' => 'sometimes|required|TimeFormat',
				'day_5_time_out' => 'sometimes|required|TimeFormat',
				'day_6_time_in' => 'sometimes|required|TimeFormat',
				'day_6_time_out' => 'sometimes|required|TimeFormat'
			)
		);

		if ($validator->fails())
		{
			return Redirect::back()->withInput()->withErrors($validator);
		}

		$user->name = Input::get('name');
		$user->surname = Input::get('surname');
		$user->level = Input::get('level');


		for($n=0;$n<7;$n++){
			if ( (Input::get("day_check_$n") == 1) && (Input::has(array("day_${n}_time_in", "day_${n}_time_out"))) ) {
				UsersTimes::where('user_id', $id)->where('weekday', $n)->delete();

				$weekday = new UsersTimes();
				$weekday->user_id = $id;
				$weekday->weekday = $n;
				$weekday->time_in = Input::get("day_".$n."_time_in");
				$weekday->time_out = Input::get("day_".$n."_time_out");
				$weekday->save();
			} else {
				$weekday = UsersTimes::where('user_id', $id)->where('weekday', $n)->delete();
			}
		}

		if (strlen(Input::get('password'))) {
			$user->password = Hash::make(Input::get('password'));
		}

		$user->save();

		$messages = with(new MessageBag())->add('success', 'Usuário modificado com sucesso!');

		return Redirect::route('user.index')->with('messages', $messages);
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if (!$this->checkPermission(2)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}
		$user = User::findOrFail($id);
		$messages = new MessageBag();

		if (Auth::user()->id == $user->id) {
			$messages->add('danger', 'Você não pode excluir o próprio usuário.');
		} else {
			if (Auth::user()->level >= $user->level) {
				if ($user->delete()) {
					$messages->add('success', 'Usuário excluído com sucesso!');
					return Redirect::route('user.index')->with('messages', $messages);
				} else {
					$messages->add('danger', 'Não foi possível excluir usuário!');
				}
			} else {
				$messages->add('danger', 'Você não possui permissão para excluir esse usuário.');
			}
		}

		return Redirect::back()->withInput()->with('messages', $messages);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @return Response
	 */
	public function multiple_destroy()
	{
		if (!$this->checkPermission(2)) {
			return Redirect::route('home.dashboard')->with("permission_denied", true);
		}
		$ids = Input::get('id');
		$success = [];
		$error = [];
		$denied = [];
		$messages = new MessageBag();
		
		foreach ($ids as $id) {
			$user = User::findOrFail($id);

			if (Auth::user()->id == $user->id) {
				$messages->add('danger', 'Você não pode excluir o próprio usuário.');
			} else {
				if (Auth::user()->level >= $user->level) {
					if ($user->delete()) {
						$success[] = $id;
					} else {
						$error[] = $id;
					}
				} else {
					$denied[] = $id;
				}
			}
		}

		/*
			Agrupa mensagens de sucesso 
		*/
			
		if (count($success) > 0) {
			$message = "";
			foreach ($success as $id) {
				$message .= $id . ", ";
			}
			$message = substr($message, 0, -2);
			if (count($success) == 1) {
				$message = "Usuário #" . $message . " excluído com sucesso.";
			} else if (count($success) > 1) {
				$message = "Usuários #" . $message . " excluídos com sucesso.";
			}
			$messages->add('success', $message);
		}

		if (count($error) > 0) {
			$message = "";
			foreach ($error as $id) {
				$message .= $id . ", ";
			}
			$message = substr($message, 0, -2);
			if (count($error) == 1) {
				$message = "Não foi possível excluir o usuário #" . $message . ".";
			} else if (count($error) > 1) {
				$message = "Não foi possível excluir os usuários #" . $message . ".";
			}
			$messages->add('danger', $message);
		}

		if (count($denied) > 0) {
			$message = "";
			foreach ($denied as $id) {
				$message .= $id . ", ";
			}
			$message = substr($message, 0, -2);
			if (count($denied) == 1) {
				$message = "Você não possui permissão para excluir o usuário #" . $message . ".";
			} else if (count($denied) > 1) {
				$message = "Você não possui permissão para excluir os usuários #" . $message . ".";
			}
			$messages->add('danger', $message);
		}

		return Redirect::back()->withInput()->with('messages', $messages);
	}

}
