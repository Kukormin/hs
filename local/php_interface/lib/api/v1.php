<?

namespace Local\Api;

use Local\Data\Claim;
use Local\Data\Deal;
use Local\Data\Feed;
use Local\Data\Options;
use Local\Data\Status;
use Local\User\Auth;
use Local\User\User;
use Local\Data\Faq;
use Local\Data\Ad;
use Local\Data\News;
use Local\Catalog\Condition;
use Local\Catalog\Color;
use Local\Catalog\Catalog;
use Local\Catalog\Size;
use Local\Catalog\Payment;
use Local\Catalog\Delivery;
use Local\Catalog\Brand;
use Local\Catalog\Gender;

class v1 extends Api
{
	protected function faq() {
		return Faq::getAll(true);
	}

	protected function options() {
		return Options::getAll(true);
	}

	protected function auth($args) {
		$method = $args[0];
		if ($method == 'phone')
			return Auth::step1($this->post['phone']);
		if ($method == 'phone_debug')
			return Auth::step1_debug($this->post['phone']);
		elseif ($method == 'verify')
			return Auth::step2($this->post['phone'], $this->post['code'], $this->post['user'],
				$this->post['device']);
		elseif ($method == 'setpt')
			return Auth::setPt($this->post['pt']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function user($args) {
		$method = $args[0];
		if ($method == 'profile')
			return User::profile();
		if ($method == 'public')
			return User::publicProfileFull($args[1], $args[2], $this->request);
		elseif ($method == 'nickname')
			return User::nickname($this->post['nickname']);
		elseif ($method == 'update')
			return User::update($this->post);
		elseif ($method == 'follow')
			return User::follow($this->post['publisher']);
		elseif ($method == 'unfollow')
			return User::unfollow($this->post['publisher']);
		elseif ($method == 'favorite')
		{
			if ($args[1] == 'add')
				return User::addToFavorite($this->post['ad']);
			elseif ($args[1] == 'remove')
				return User::removeFromFavorite($this->post['ad']);
			elseif ($args[1] == 'list')
				return User::favorites($this->request);
			elseif ($args[1] == 'count')
				return User::favoritesCount();
			else
				throw new ApiException(['wrong_endpoint'], 404);
		}
		elseif ($method == 'search')
			return User::search($this->request);
		elseif ($method == 'news')
			return News::getAppData($this->request);
		elseif ($method == 'myads')
			return User::getMyAds($args[1], $this->request);
		elseif ($method == 'support')
			return User::message(0, $this->post['message']);
		elseif ($method == 'supportchat')
			return User::chat($this->request);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function catalog($args) {
		$method = $args[0];
		if ($method == 'condition')
			return Condition::getAppData();
		elseif ($method == 'color')
			return Color::getAppData();
		elseif ($method == 'section')
			return Catalog::getAppData();
		elseif ($method == 'size')
			return Size::getAppData($args[1]);
		elseif ($method == 'payment')
			return Payment::getAppData();
		elseif ($method == 'delivery')
			return Delivery::getAppData();
		elseif ($method == 'gender')
			return Gender::getAppData();
		elseif ($method == 'brand')
			return Brand::getAppData();
		elseif ($method == 'addbrand')
			return Brand::add($this->post['name']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function ad($args) {
		$method = $args[0];
		if ($method == 'add')
			return Ad::add($this->post);
		elseif ($method == 'update')
			return Ad::update($this->post['ad'], $this->post);
		elseif ($method == 'delete')
			return Ad::delete($this->post['ad']);
		elseif ($method == 'list')
			return Ad::getList($this->request, true);
		elseif ($method == 'comment')
			return Ad::comment($this->post['ad'], $this->post['message']);
		elseif ($method == 'comments')
			return Ad::comments($args[1], $this->request);
		elseif ($method == 'detail')
			return Ad::detail($args[1]);
		elseif ($method == 'share')
			return Ad::share($this->post['ad']);
		elseif ($method == 'social')
			return Ad::socialShare($this->post['ad'], $this->post['sn']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function feed() {
		return Feed::getAppData($this->request);
	}

	protected function claim($args) {
		$method = $args[0];
		if ($method == 'reasons')
			return Claim::getAppData();
		elseif ($method == 'add')
			return Claim::add($this->post['ad'], $this->post['reason']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}

	protected function deal($args) {
		$method = $args[0];
		if ($method == 'statuses')
			return Status::getAppData();
		elseif ($method == 'add')
			return User::addDeal($this->post['ads'], $this->post['payment'], $this->post['delivery'],
				$this->post['check'], $this->post['address']);
		elseif ($method == 'append')
			return User::appendAdToDeal($this->post['ad'], $this->post['deal']);
		elseif ($method == 'adremove')
			return User::removeAdFromDeal($this->post['ad'], $this->post['deal']);
		elseif ($method == 'update')
			return User::updateDealStatus($this->post['deal'], $this->post['status'], $this->post['price'],
				$this->post['track']);
		elseif ($method == 'addTrack')
			return User::addTrack($this->post['deal'], $this->post['track']);
		elseif ($method == 'track')
			return User::trackDeal($this->post['deal']);
		elseif ($method == 'my')
			return User::getMyDeals($args[1], $this->request);
		elseif ($method == 'detail')
			return Deal::detail($args[1]);
		elseif ($method == 'message')
			return Deal::message(0, $this->post['deal'], $this->post['message'], false);
		elseif ($method == 'support')
			return Deal::message(0, $this->post['deal'], $this->post['message'], true);
		elseif ($method == 'chat')
			return Deal::chat($args[1], false, $this->request);
		elseif ($method == 'supportchat')
			return Deal::chat($args[1], true, $this->request);
		elseif ($method == 'rating')
			return Deal::rating($this->post['deal'], $this->post['rating'], $this->post['text']);
		else
			throw new ApiException(['wrong_endpoint'], 404);
	}
}