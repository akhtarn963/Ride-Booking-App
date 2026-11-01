
import { useEffect, useState } from 'react'
import './App.css'
import {
  estimateFare,
  createRide,
  getRide,
  cancelRide,
  type FareEstimate,
  type Ride,
  type VehicleCategory,
} from './services/rideApi'

type Location = {
  name: string
  latitude: number
  longitude: number
}

const locations: Location[] = [
  { name: 'Liberty Market', latitude: 31.5112, longitude: 74.3436 },
  { name: 'Packages Mall', latitude: 31.4670, longitude: 74.3555 },
  { name: 'Lahore Railway Station', latitude: 31.5775, longitude: 74.3290 },
  { name: 'Minar-e-Pakistan', latitude: 31.5925, longitude: 74.3095 },
  { name: 'Emporium Mall', latitude: 31.4638, longitude: 74.2650 },
  { name: 'Gaddafi Stadium', latitude: 31.5134, longitude: 74.3330 },
  { name: 'DHA Phase 5', latitude: 31.4697, longitude: 74.4090 },
  { name: 'Mall Road', latitude: 31.5656, longitude: 74.3242 },
]

function App() {
  const [pickup, setPickup] = useState('Liberty Market')
  const [destination, setDestination] = useState('Packages Mall')
  const [vehicle, setVehicle] = useState<VehicleCategory>('economy')

  const [estimate, setEstimate] = useState<FareEstimate | null>(null)
  const [estimateLoading, setEstimateLoading] = useState(false)
  const [estimateError, setEstimateError] = useState('')

  const [ride, setRide] = useState<Ride | null>(null)
  const [bookingLoading, setBookingLoading] = useState(false)
  const [rideError, setRideError] = useState('')
  const [idempotencyKey, setIdempotencyKey] = useState<string | null>(null)

  const pickupLocation = locations.find((location) => location.name === pickup)
  const destinationLocation = locations.find((location) => location.name === destination)

  const canRequest = Boolean(
    pickupLocation &&
    destinationLocation &&
    pickup !== destination
  )

  // Automatically calculate the fare when the route or vehicle changes.
  useEffect(() => {
    if (!canRequest || !pickupLocation || !destinationLocation || ride) {
      setEstimate(null)
      return
    }

    let cancelled = false

    const timer = window.setTimeout(async () => {
      setEstimateLoading(true)
      setEstimateError('')

      try {
        const result = await estimateFare({
          pickup_latitude: pickupLocation.latitude,
          pickup_longitude: pickupLocation.longitude,
          destination_latitude: destinationLocation.latitude,
          destination_longitude: destinationLocation.longitude,
          vehicle_category: vehicle,
        })

        if (!cancelled) {
          setEstimate(result)
        }
      } catch (error) {
        if (!cancelled) {
          setEstimateError(
            error instanceof Error ? error.message : 'Unable to calculate fare.',
          )
          setEstimate(null)
        }
      } finally {
        if (!cancelled) {
          setEstimateLoading(false)
        }
      }
    }, 400)

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [pickup, destination, vehicle, canRequest, ride])

  // Refresh ride status periodically while the ride is active.
  useEffect(() => {
    if (!ride || ['completed', 'cancelled'].includes(ride.status)) {
      return
    }

    const timer = window.setInterval(async () => {
      try {
        const updatedRide = await getRide(ride.id)
        setRide(updatedRide)
      } catch {
        // Keep the current ride information if a refresh temporarily fails.
      }
    }, 5000)

    return () => window.clearInterval(timer)
  }, [ride?.id, ride?.status])

  async function handleRequestRide() {
    if (!canRequest || !pickupLocation || !destinationLocation || !estimate) {
      return
    }

    setBookingLoading(true)
    setRideError('')

    const key = idempotencyKey ?? crypto.randomUUID()
    setIdempotencyKey(key)

    try {
      const newRide = await createRide(
        {
          rider_id: 1,
          pickup_location: pickupLocation.name,
          pickup_latitude: pickupLocation.latitude,
          pickup_longitude: pickupLocation.longitude,
          destination_location: destinationLocation.name,
          destination_latitude: destinationLocation.latitude,
          destination_longitude: destinationLocation.longitude,
          vehicle_category: vehicle,
        },
        key,
      )

      setRide(newRide)
      setIdempotencyKey(null)
    } catch (error) {
      setRideError(
        error instanceof Error ? error.message : 'Unable to request your ride.',
      )
    } finally {
      setBookingLoading(false)
    }
  }

  async function handleCancelRide() {
    if (!ride) return

    setBookingLoading(true)
    setRideError('')

    try {
      const updatedRide = await cancelRide(ride.id)
      setRide(updatedRide)
    } catch (error) {
      setRideError(
        error instanceof Error ? error.message : 'Unable to cancel your ride.',
      )
    } finally {
      setBookingLoading(false)
    }
  }

  function resetBooking() {
    setRide(null)
    setEstimate(null)
    setEstimateError('')
    setRideError('')
    setIdempotencyKey(null)
  }

  function formatStatus(status: string) {
    return status.replace('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
  }

  return (
    <div className="app">
      <header className="topbar">
        <a className="brand" href="/">
          <span className="brand-icon">R</span>
          <span>Ride<span className="brand-highlight">Lahore</span></span>
        </a>

        <nav className="desktop-nav">
          <a href="#book">Book a ride</a>
          <a href="#how-it-works">How it works</a>
        </nav>

        <div className="header-right">
          <span className="location-label">📍 Lahore, Pakistan</span>
          <button className="sign-in" type="button">Demo rider</button>
        </div>
      </header>

      <main>
        <section className="hero">
          <div className="hero-content">
            <span className="eyebrow">YOUR CITY. YOUR RIDE.</span>
            <h1>Get there.<br /><span>On your terms.</span></h1>
            <p>Reliable rides around Lahore, whenever you need them. Choose your ride and enjoy the journey.</p>
            <div className="hero-points">
              <span>✓ Simple booking</span>
              <span>✓ Upfront estimates</span>
              <span>✓ Local rides</span>
            </div>
          </div>

          <div className="hero-art" aria-label="Illustration of a car driving through Lahore">
            <div className="sun"></div>
            <div className="skyline skyline-back"></div>
            <div className="skyline skyline-front"></div>
            <div className="road"></div>
            <div className="car">
              <div className="car-top"></div>
              <div className="car-body">
                <span className="car-window"></span>
                <span className="car-light"></span>
              </div>
              <div className="wheel wheel-left"></div>
              <div className="wheel wheel-right"></div>
            </div>
            <span className="art-caption">MADE FOR LAHORE</span>
          </div>
        </section>

        <section className="booking-section" id="book">
          <div className="booking-heading">
            <div>
              <span className="section-kicker">LET'S GET MOVING</span>
              <h2>{ride ? 'Your ride details' : 'Where are you going?'}</h2>
              <p>{ride ? 'Follow your ride status below.' : 'Choose your pickup and destination in Lahore.'}</p>
            </div>
            <span className="booking-step">01 <span>/ 03</span></span>
          </div>

          <div className="booking-layout">
            <div className="booking-card">
              {!ride ? (
                <>
                  <div className="location-fields">
                    <div className="route-indicator">
                      <span className="pickup-dot"></span>
                      <span className="route-line"></span>
                      <span className="destination-dot"></span>
                    </div>

                    <div className="field-group">
                      <label htmlFor="pickup">PICKUP LOCATION</label>
                      <select
                        id="pickup"
                        value={pickup}
                        onChange={(event) => {
                          setPickup(event.target.value)
                          setIdempotencyKey(null)
                        }}
                      >
                        {locations.map((location) => (
                          <option key={location.name} value={location.name}>
                            {location.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div className="field-divider"></div>

                    <div className="field-group">
                      <label htmlFor="destination">DROP-OFF LOCATION</label>
                      <select
                        id="destination"
                        value={destination}
                        onChange={(event) => {
                          setDestination(event.target.value)
                          setIdempotencyKey(null)
                        }}
                      >
                        {locations.map((location) => (
                          <option key={location.name} value={location.name}>
                            {location.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="vehicle-heading">
                    <div>
                      <h3>Choose your ride</h3>
                      <p>Select the ride that suits you.</p>
                    </div>
                  </div>

                  <div className="vehicle-options">
                    <button
                      type="button"
                      className={`vehicle-option ${vehicle === 'economy' ? 'selected' : ''}`}
                      onClick={() => setVehicle('economy')}
                      aria-pressed={vehicle === 'economy'}
                    >
                      <span className="vehicle-emoji">🚗</span>
                      <span className="vehicle-info">
                        <strong>Economy</strong>
                        <small>Affordable everyday rides</small>
                      </span>
                      <span className="vehicle-radio"></span>
                    </button>

                    <button
                      type="button"
                      className={`vehicle-option ${vehicle === 'comfort' ? 'selected' : ''}`}
                      onClick={() => setVehicle('comfort')}
                      aria-pressed={vehicle === 'comfort'}
                    >
                      <span className="vehicle-emoji">🚙</span>
                      <span className="vehicle-info">
                        <strong>Comfort</strong>
                        <small>Extra comfort for your journey</small>
                      </span>
                      <span className="vehicle-radio"></span>
                    </button>
                  </div>

                  <div className="estimate-placeholder">
                    <span className="estimate-icon">↗</span>
                    <div>
                      <strong>Fare and arrival estimate</strong>
                      {estimateLoading ? (
                        <p>Calculating your fare...</p>
                      ) : estimate ? (
                        <p>
                          {estimate.distance_km.toFixed(1)} km · PKR{' '}
                          {Number(estimate.estimated_fare).toFixed(0)} ·{' '}
                          {estimate.estimated_duration_minutes} minutes
                        </p>
                      ) : estimateError ? (
                        <p className="api-error">{estimateError}</p>
                      ) : !canRequest ? (
                        <p>Please choose two different locations.</p>
                      ) : (
                        <p>Calculating your estimate...</p>
                      )}
                    </div>
                  </div>

                  {rideError && <p className="api-error">{rideError}</p>}

                  <button
                    className="request-button"
                    type="button"
                    disabled={!canRequest || !estimate || estimateLoading || bookingLoading}
                    onClick={handleRequestRide}
                  >
                    {bookingLoading ? 'Requesting...' : 'Request a ride'} <span>→</span>
                  </button>
                  <p className="booking-note">No payment required to request a ride.</p>
                </>
              ) : (
                <div className="ride-result">
                  <div className="ride-status">
                    <span>RIDE #{ride.id}</span>
                    <strong>{formatStatus(ride.status)}</strong>
                  </div>

                  <div className="ride-route">
                    <p> <span className="pickup-dot"></span> {ride.pickup_location}</p>
                    <p><span className="destination-dot"></span> {ride.destination_location}</p>
                  </div>

                  <div className="ride-summary">
                    <div>
                      <small>Estimated fare</small>
                      <strong>PKR {Number(ride.estimated_fare).toFixed(0)}</strong>
                    </div>
                    <div>
                      <small>Estimated time</small>
                      <strong>{ride.estimated_duration_minutes} min</strong>
                    </div>
                    <div>
                      <small>Vehicle</small>
                      <strong>{formatStatus(ride.vehicle_category)}</strong>
                    </div>
                  </div>

                  {ride.active_assignment?.driver ? (
                    <div className="driver-details">
                      <h3>Your driver</h3>
                      <p>{ride.active_assignment.driver.name}</p>
                      <p>
                        {ride.active_assignment.driver.vehicle_model} ·{' '}
                        {ride.active_assignment.driver.vehicle_number}
                      </p>
                    </div>
                  ) : (
                    ride.status === 'requested' && (
                      <p className="booking-note">
                        Looking for an available driver. Your ride request is saved.
                      </p>
                    )
                  )}

                  {rideError && <p className="api-error">{rideError}</p>}

                  {['requested', 'accepted'].includes(ride.status) && (
                    <button
                      className="request-button"
                      type="button"
                      disabled={bookingLoading}
                      onClick={handleCancelRide}
                    >
                      {bookingLoading ? 'Please wait...' : 'Cancel ride'}
                    </button>
                  )}

                  {['completed', 'cancelled'].includes(ride.status) && (
                    <button
                      className="request-button"
                      type="button"
                      onClick={resetBooking}
                    >
                      Book another ride →
                    </button>
                  )}

                  <p className="booking-note">Ride status refreshes automatically.</p>
                </div>
              )}
            </div>

            <aside className="booking-aside">
              <div className="aside-card">
                <span className="aside-icon">📍</span>
                <h3>Your city, your journey</h3>
                <p>Choose from popular locations around Lahore.</p>
              </div>
              <div className="aside-card">
                <span className="aside-icon">🛡️</span>
                <h3>Simple and transparent</h3>
                <p>See your estimated fare before requesting your ride.</p>
              </div>
              <div className="aside-card">
                <span className="aside-icon">⚡</span>
                <h3>Quick booking</h3>
                <p>Your request is automatically matched with an available driver in your vehicle category.</p>
              </div>
            </aside>
          </div>
        </section>

        <section className="how-section" id="how-it-works">
          <span className="section-kicker">THREE SIMPLE STEPS</span>
          <h2>Your ride, made easy.</h2>
          <div className="steps">
            <div className="step">
              <span>01</span>
              <h3>Choose locations</h3>
              <p>Select your pickup and destination in Lahore.</p>
            </div>
            <div className="step">
              <span>02</span>
              <h3>Select your ride</h3>
              <p>Pick Economy or Comfort to match your journey.</p>
            </div>
            <div className="step">
              <span>03</span>
              <h3>Enjoy the ride</h3>
              <p>Request a driver and follow your ride status.</p>
            </div>
          </div>
        </section>
      </main>

      <footer className="footer">
        <a className="brand" href="/">
          <span className="brand-icon">R</span>
          <span>Ride<span className="brand-highlight">Lahore</span></span>
        </a>
        <p>Making everyday journeys simpler.</p>
        <span>© 2026 RideLahore</span>
      </footer>
    </div>
  )
}

export default App
